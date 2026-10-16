// https://docs.expo.dev/guides/using-eslint/
const { defineConfig } = require('eslint/config');
const expoConfig = require('eslint-config-expo/flat');

// Jest injects these into test files; without them every test file fails
// lint with no-undef. Scoped to test files so app code can't use them.
const jestGlobals = Object.fromEntries(
  ['jest', 'describe', 'it', 'test', 'expect', 'beforeEach', 'afterEach', 'beforeAll', 'afterAll', '__dirname']
    .map((name) => [name, 'readonly'])
);

module.exports = defineConfig([
  expoConfig,
  {
    ignores: ['dist/*'],
  },
  {
    files: ['**/__tests__/**/*.{js,jsx}', '**/*.test.{js,jsx}'],
    languageOptions: { globals: jestGlobals },
  },
]);
