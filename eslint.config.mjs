import js from '@eslint/js'
import { FlatCompat } from '@eslint/eslintrc'

const compat = new FlatCompat({
	baseDirectory: import.meta.dirname,
	recommendedConfig: js.configs.recommended,
})

export default [
	{
		ignores: [
			'js/**',
			'l10n/**',
			'node_modules/**',
			'vendor/**',
		],
	},
	...compat.extends('@nextcloud'),
	{
		files: ['src/**/*.{js,ts,vue}'],
		languageOptions: {
			globals: {
				$: 'readonly',
				OC: 'readonly',
			},
		},
		rules: {
			'@typescript-eslint/no-explicit-any': 'off',
			'n/no-extraneous-import': 'off',
			'n/no-unpublished-import': 'off',
		},
	},
]
