export default {
  root: true,
  extends: [
    "eslint:recommended",
    "@typescript-eslint/recommended",
    "plugin:svelte/recommended",
  ],
  parser: "@typescript-eslint/parser",
  plugins: ["@typescript-eslint"],
  parserOptions: {
    sourceType: "module",
    ecmaVersion: 2020,
    extraFileExtensions: [".svelte"],
  },
  env: {
    browser: true,
    es2017: true,
    node: true,
  },
  overrides: [
    {
      files: ["*.svelte"],
      parser: "svelte-eslint-parser",
      parserOptions: {
        parser: "@typescript-eslint/parser",
      },
    },
  ],
  rules: {
    "@typescript-eslint/no-unused-vars": ["error", { argsIgnorePattern: "^_" }],
    "svelte/no-target-blank": "error",
    "svelte/no-at-debug-tags": "warn",
    "svelte/no-reactive-functions": "error",
    "svelte/no-reactive-literals": "error",
  },
};
