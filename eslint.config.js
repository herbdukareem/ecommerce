import eslintPluginVue from 'eslint-plugin-vue';

export default [
  {
    ignores: ['public/**', 'vendor/**', 'node_modules/**'],
  },
  ...eslintPluginVue.configs['flat/recommended'],
  {
    files: ['resources/js/**/*.{js,vue}'],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
    },
    rules: {
      'no-console': 'off',
      'vue/multi-word-component-names': 'off',
    },
  },
];
