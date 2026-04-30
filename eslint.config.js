import pluginVue from 'eslint-plugin-vue';
import vueTsEslintConfig from '@vue/eslint-config-typescript';
import vuePrettierConfig from '@vue/eslint-config-prettier';

export default [
    {
        name: 'app/files-to-lint',
        files: ['resources/js/**/*.{ts,vue}'],
    },
    {
        name: 'app/files-to-ignore',
        ignores: ['**/node_modules/**', '**/public/**', 'vendor/**'],
    },
    ...pluginVue.configs['flat/essential'],
    ...vueTsEslintConfig(),
    vuePrettierConfig,
    {
        rules: {
            'vue/multi-word-component-names': 'off',
            'no-undef': 'off',
        },
    },
];
