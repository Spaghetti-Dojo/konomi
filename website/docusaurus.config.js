// @ts-check

import { themes as prismThemes } from 'prism-react-renderer';

/** @type {import('@docusaurus/types').Config} */
const config = {
	title: 'Konomi',
	tagline: 'Save posts as favorite using the WordPress Interactivity API',

	url: 'https://spaghetti-dojo.github.io',
	baseUrl: '/konomi/',

	organizationName: 'Spaghetti-Dojo',
	projectName: 'konomi',

	onBrokenLinks: 'throw',

	markdown: {
		hooks: {
			onBrokenMarkdownLinks: 'throw',
		},
	},

	future: {
		v4: true,
		faster: true,
	},

	i18n: {
		defaultLocale: 'en',
		locales: [ 'en' ],
	},

	presets: [
		[
			'classic',
			/** @type {import('@docusaurus/preset-classic').Options} */
			( {
				docs: {
					path: '../docs',
					routeBasePath: '/',
					sidebarPath: './sidebars.js',
					editUrl:
						'https://github.com/Spaghetti-Dojo/konomi/tree/main/docs/',
					exclude: [
						'**/_*.{js,jsx,ts,tsx,md,mdx}',
						'**/_*/**',
						'**/*.test.{js,jsx,ts,tsx}',
						'**/__tests__/**',
						'superpowers/**',
					],
				},
				blog: false,
				theme: {
					customCss: './src/css/custom.css',
				},
			} ),
		],
	],

	themeConfig:
		/** @type {import('@docusaurus/preset-classic').ThemeConfig} */
		( {
			colorMode: {
				defaultMode: 'dark',
				disableSwitch: false,
				respectPrefersColorScheme: true,
			},
			navbar: {
				title: 'Konomi',
				items: [
					{
						href: 'https://github.com/Spaghetti-Dojo/konomi',
						label: 'GitHub',
						position: 'right',
					},
				],
			},
			footer: {
				style: 'dark',
				links: [
					{
						title: 'More',
						items: [
							{
								label: 'GitHub',
								href: 'https://github.com/Spaghetti-Dojo/konomi',
							},
							{
								label: 'Issues',
								href: 'https://github.com/Spaghetti-Dojo/konomi/issues',
							},
						],
					},
				],
				copyright: `Copyright © ${ new Date().getFullYear() } Spaghetti Dojo.`,
			},
			prism: {
				theme: prismThemes.github,
				darkTheme: prismThemes.dracula,
				additionalLanguages: [ 'php' ],
			},
		} ),
};

export default config;
