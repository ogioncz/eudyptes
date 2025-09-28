#!/usr/bin/env python3
import json
import logging
import os
import re
import subprocess
import sys
import tempfile
import zipfile
from pathlib import Path

logger = logging.getLogger('create-zipfile')

DISALLOWED_FILENAME_PATTERNS = list(map(re.compile, [
	r'^\.git(hub|ignore|attributes|keep)$',
	r'^\.(appveyor|travis)\.yml$',
	r'^\.editorconfig$',
	r'(?i)^changelog',
	r'(?i)^contributing',
	r'(?i)^upgrading',
	r'(?i)^copying',
	r'(?i)^index',
	r'(?i)^readme',
	r'(?i)^licen[cs]e',
	r'^phpunit',
	r'^l?gpl\.txt$',
	r'^composer\.(json|lock)$',
	r'^package(-lock)?\.json$',
	r'^yarn\.lock$',
	r'^Makefile$',
	r'^build\.xml$',
	r'^phpcs-ruleset\.xml$',
	r'^\.php_cs$',
	r'^phpmd\.xml$',
]))

DISALLOWED_DEST_PATTERNS = list(map(re.compile, [
	r'^vendor/composer/installed\.json$',
	r'(?i)^vendor/[^/]+/[^/]+/\.?(test|doc|example|spec)s?',
	r'^vendor/[^/]+/[^/]+/\.git((hub)?/|$)',
]))

def is_not_unimportant(dest: Path) -> bool:
	filename = dest.name

	filename_disallowed = any([expr.match(filename) for expr in DISALLOWED_FILENAME_PATTERNS])

	dest_disallowed = any([expr.match(str(dest)) for expr in DISALLOWED_DEST_PATTERNS])

	allowed = not (filename_disallowed or dest_disallowed)

	return allowed

class ZipFile(zipfile.ZipFile):
	def directory(self, name, allowed=None):
		if allowed is None:
			allowed = lambda item: True

		for root, dirs, files in os.walk(name):
			root = Path(root)

			for directory in dirs:
				directory = Path(directory)
				path = root / directory

				if allowed(path):
					# Directories are empty files whose path ends with a slash.
					# https://mail.python.org/pipermail/python-list/2003-June/205859.html
					self.writestr(str(self.prefix / path) + '/', '')

			for file in files:
				path = root / file

				if allowed(path):
					self.write(path, self.prefix / path)
	def file(self, name):
		self.write(name, self.prefix / name)

def main():
	source_dir = Path.cwd()
	with tempfile.TemporaryDirectory(prefix='eudyptes-dist-') as temp_dir:
		dirty = subprocess.run(['git','-C', source_dir, 'diff-index', '--quiet', 'HEAD']).returncode == 1
		if dirty:
			logger.warning('Repository contains uncommitted changes that will not be included in the dist archive.')

		logger.info('Cloning the repository into a temporary directory…')
		subprocess.check_call(['git', 'clone', '--shared', source_dir, temp_dir])

		os.chdir(temp_dir)

		if len(sys.argv) >= 2:
			filename = sys.argv[1]
		else:
			short_commit = subprocess.check_output(['git', 'rev-parse', '--short', 'HEAD'], encoding='utf-8').strip()
			filename = f'eudyptes-{short_commit}.zip'

		logger.info('Installing frontend dependencies…')
		subprocess.check_call(['npm', 'install'])

		logger.info('Copying assets…')
		subprocess.check_call(['npx', 'gulp'])

		logger.info('Installing and optimizing backend dependencies…')
		subprocess.check_call(['composer', 'install', '--no-dev', '--optimize-autoloader'])

		# fill archive with data
		with ZipFile(
			source_dir / filename,
			'w',
			zipfile.ZIP_DEFLATED,
			strict_timestamps=False,
		) as archive:
			archive.prefix = Path('eudyptes')

			archive.directory('www/')

			archive.directory('app/')
			archive.directory('log/')
			archive.directory('temp/')
			archive.directory('migrations/')
			archive.directory('vendor/', is_not_unimportant)

			archive.file('README.md')

			logger.info('Zipball ‘{}’ has been successfully generated.'.format(filename))

if __name__ == '__main__':
	main()
