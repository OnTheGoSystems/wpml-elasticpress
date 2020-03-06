#!/usr/bin/env node

const fs   = require('fs-extra');
const path = require('path');

const yargs = require('yargs')
	.usage('Usage: [options]')
	.wrap(null)
	.example(getHelp())
	.option('t', {
		description: 'The target path to update',
		alias:       'target',
		default:     process.cwd(),
		string:      true
	})
	.option('r', {
		description:  'The version',
		alias:        'ref',
		demandOption: true,
		string:       true,
	})
	.option('d', {
		description: 'A hard-coded JSON array will be used instead of attempting to read ti from the environment variable.',
		alias:       'debug',
		boolean:     true,
		default:     false
	});
const argv  = yargs
	.argv;

const targetPath = path.normalize(argv.target);

function getHelp() {
	const help = [];
	if (!hasEnvironmentVariable()) {
		help.push('You must set a constant named OTGS_CI_REPLACEMENTS containing a JSON array in this form:');
		help.push('');
		help.push(':: Example:');
		const sample = [
			{
				"searchPattern":      "(Version:\\s+)(\\S+)",
				"replacePattern":     "%1{{tag}}",
				"displayFullVersion": true
			},
			{
				"searchPattern":  "(wpml.org\\/version\\/wpml-)(\\S+)(\\/\">WPML )(\\S+)( release notes)",
				"replacePattern": "%1{{tag-slug}}%3{{tag}}%5"
			},
			{
				"searchPattern":      "(define\\s*\\(\\s*\\'ICL_SITEPRESS_VERSION\\'\\s*,\\s*\\')(\\S*)(\\'\\s*\\);)",
				"replacePattern":     "%1{{tag}}%3",
				"displayFullVersion": false
			},
		];
		// help.push('[');
		// sample.map(item => help.push(JSON.stringify(item,null,2)));
		// help.push(']');
		help.push(JSON.stringify(sample, null, 2));

		help.push('');

		help.push('- searchPattern: the regular expression to use for searching the string to replace.');
		help.push('- replacePattern: the replacement expression');
		help.push('  - "{{tag}}": the placeholder for the tag string');
		help.push('  - "{{tag-slug}}": the placeholder for the URL-friendly tag string');
		help.push('"- displayFullVersion": when set to false, it removes the prerelease component of the version (e.g. "1.2.3-b.1" becomes "1.2.3"');
		help.push('');
	}
	return help.join('\n');
}

function updatePluginVersion() {
	const tag          = argv.ref.trim();
	const replacements = getReplacements();

	if (replacements) {
		const currentDirectory = process.cwd();
		process.chdir(targetPath);

		const mainPluginFile = getMainPluginFile();

		if (mainPluginFile) {
			const file         = mainPluginFile.file;
			const content      = mainPluginFile.content;
			let updatedContent = content;

			console.info('- Found "' + file + '": updating...');

			const replacement_patterns = JSON.parse(replacements.replace(/(%)(\d)/g, '$$$2'));

			replacement_patterns
				.filter(regex_args => {
					const regExp = new RegExp(regex_args.searchPattern, 'g');
					return regExp.test(updatedContent);
				})
				.map((regex_args, index) => {

					const usePrerelease = (() => {
						if (regex_args.hasOwnProperty('displayFullVersion')) {
							return !!regex_args.displayFullVersion;
						}
						if (regex_args.hasOwnProperty('extractSemVer')) {
							return !!regex_args.extractSemVer;
						}
						return true;
					})();

					const tagName = extractSemVer(tag, usePrerelease);
					const tagSlug = tagName.trim().replace(/\./g, '-');

					const regExp = new RegExp(regex_args.searchPattern, 'g');

					if (regExp.test(updatedContent)) {
						process.stdout.write((index + 1) + ') Will search for "' + regex_args.searchPattern);
						process.stdout.write(' using "' + tagName + '" as a tag and "' + tagSlug + '" as a tag slug');
						process.stdout.write(' and replacing it with "' + regex_args.replacePattern + '"\n');

						updatedContent = updatedContent.replace(regExp, regex_args.replacePattern)
							.replace(/{{tag-slug}}/g, tagSlug)
							.replace(/{{tag}}/g, tagName);
					}
				});

			if (!argv.dryRun && updatedContent !== content) {
				fs.writeFileSync(file, updatedContent, {encoding: 'utf8'});
			}

			process.chdir(currentDirectory);
		}
	} else {
		console.info('A constant named OTGS_CI_REPLACEMENTS hasn\'t been set: skipping.');
		console.info('Example:');
		console.info(getTestPatterns());
	}
}

function extractSemVer(version, withPrerelease = true) {
	const sanitizedVersion = version
		.trim()
		.replace(/\.{2,}/g, '.');

	const [versionNumber, prerelease = ''] = sanitizedVersion.split('-');

	const versionElements = versionNumber.split('.');

	const sanitizedVersionElements = versionElements.filter(element => !isNaN(element));

	return sanitizedVersionElements.join('.')
		+ (
			(withPrerelease && prerelease) ? ('-' + prerelease) : ''
		);
}

function getMainPluginFile() {
	const files = fs.readdirSync(process.cwd());

	const phpFiles = files
		.filter(file => path.extname(file).toLowerCase() === '.php')
		.filter(file => {

			const content = fs.readFileSync(file, 'utf8')
				.replace(/[\t\n\r]/g, '')
				.trim();

			return content.indexOf('<?php') === 0
				&& content.indexOf('Plugin Name: ') > 0
				&& content.indexOf('Description: ') > 0;

		});

	if (phpFiles) {
		const file = phpFiles[0];
		return {file, content: fs.readFileSync(file, 'utf8')};
	}
	return null;
}

function getTestPatterns() {
	return JSON.stringify(
		[
			{
				"searchPattern":      "(Version:\\s+)(\\S+)",
				"replacePattern":     "%1{{tag}}",
				"displayFullVersion": true
			},
			{
				"searchPattern":      "(define\\s*\\(\\s*\\'GRAVITYFORMS_MULTILINGUAL_VERSION\\'\\s*,\\s*\\')(\\S*)(\\'\\s*\\);)",
				"replacePattern":     "%1{{tag}}%3",
				"displayFullVersion": false
			},
			{
				"searchPattern":  "(wpml.org\\/version\\/wpml-)(\\S+)(\\/\">WPML )(\\S+)( release notes)",
				"replacePattern": "%1{{tag-slug}}%3{{tag}}%5"
			},
			{
				"searchPattern":      "(define\\s*\\(\\s*\\'WCML_VERSION\\'\\s*,\\s*\\')(\\S*)(\\'\\s*\\);)",
				"replacePattern":     "%1{{tag}}%3",
				"displayFullVersion": false
			},
			{
				"searchPattern":      "(wpml.org\\/version\\/cms-nav-)([\\d\\-*]*)(\\/\">WPML CMS Nav )(\\S+)( release notes)",
				"replacePattern":     "%1{{tag-slug}}%3{{tag}}%5",
				"displayFullVersion": true
			},
			{
				"searchPattern":      "(wpml.org\\/version\\/gravityforms-multilingual-)(\\S+)(\\/\">Gravity Forms Multilingual )(\\S+)( release notes)",
				"replacePattern":     "%1{{tag-slug}}%3{{tag}}%5",
				"displayFullVersion": true
			},
			{
				"searchPattern":      "(wpml.org\\/version\\/media-translation-)(\\S+)(\\/\">WPML Media Translation )(\\S+)( release notes)",
				"replacePattern":     "%1{{tag-slug}}%3{{tag}}%5",
				"displayFullVersion": true
			},
			{
				"searchPattern":      "(wpml.org\\/version\\/sticky-links-)(\\S+)(\\/\">WPML Sticky Links )(\\S+)( release notes)",
				"replacePattern":     "%1{{tag-slug}}%3{{tag}}%5",
				"displayFullVersion": true
			},
			{
				"searchPattern":      "(wpml.org\\/version\\/string-translation-)(\\S+)(\\/\">WPML String Translation )(\\S+)( release notes)",
				"replacePattern":     "%1{{tag-slug}}%3{{tag}}%5",
				"displayFullVersion": true
			},
			{
				"searchPattern":      "(wpml.org\\/version\\/translation-management-)(\\S+)(\\/\">WPML Translation Management )(\\S+)( release notes)",
				"replacePattern":     "%1{{tag-slug}}%3{{tag}}%5",
				"displayFullVersion": true
			},
			{
				"searchPattern":      "(define\\s*\\(\\s*\\'ICL_SITEPRESS_VERSION\\'\\s*,\\s*\\')(\\S*)(\\'\\s*\\);)",
				"replacePattern":     "%1{{tag}}%3",
				"displayFullVersion": false
			},
			{
				"searchPattern":      "(define\\s*\\(\\s*\\'WPML_CMS_NAV_VERSION\\'\\s*,\\s*\\')(\\S*)(\\'\\s*\\);)",
				"replacePattern":     "%1{{tag}}%3",
				"displayFullVersion": false
			},
			{
				"searchPattern":      "(define\\s*\\(\\s*\\'WPML_MEDIA_VERSION\\'\\s*,\\s*\\')(\\S*)(\\'\\s*\\);)",
				"replacePattern":     "%1{{tag}}%3",
				"displayFullVersion": false
			},
			{
				"searchPattern":      "(define\\s*\\(\\s*\\'WPML_ST_VERSION\\'\\s*,\\s*\\')(\\S*)(\\'\\s*\\);)",
				"replacePattern":     "%1{{tag}}%3",
				"displayFullVersion": false
			},
			{
				"searchPattern":      "(define\\s*\\(\\s*\\'WPML_STICKY_LINKS_VERSION\\'\\s*,\\s*\\')(\\S*)(\\'\\s*\\);)",
				"replacePattern":     "%1{{tag}}%3",
				"displayFullVersion": false
			},
			{
				"searchPattern":      "(define\\s*\\(\\s*\\'WPML_TM_VERSION\\'\\s*,\\s*\\')(\\S*)(\\'\\s*\\);)",
				"replacePattern":     "%1{{tag}}%3",
				"displayFullVersion": false
			}
		]
	);
}

function hasEnvironmentVariable() {
	return process.env.OTGS_CI_REPLACEMENTS
		|| process.env.OTGS_CI_MAIN_FILE_REPLACEMENTS
		|| process.env.OTGS_CI_REPLACEMENTS;
}

function getReplacements() {
	if (argv.debug) {
		return getTestPatterns();
	}

	if (hasEnvironmentVariable()) {
		if (process.env.OTGS_CI_REPLACEMENTS) {
			return process.env.OTGS_CI_REPLACEMENTS;
		}

		if (process.env.OTGS_CI_MAIN_FILE_REPLACEMENTS) {
			return process.env.OTGS_CI_MAIN_FILE_REPLACEMENTS;
		}

		if (process.env.OTGS_CI_REPLACEMENTS) {
			return process.env.OTGS_CI_REPLACEMENTS;
		}
	}
}

updatePluginVersion();
