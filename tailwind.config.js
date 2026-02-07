module.exports = {
  content: [
    // Tailwind should scan Vue components and JavaScript files under
    // resources/js now that the frontend has been merged directly into
    // the Laravel project.
    './resources/js/**/*.{vue,js,ts,jsx,tsx}',
    // Also scan Blade templates for utility classes.
    './resources/views/**/*.blade.php',
  ],
  theme: {
    extend: {},
  },
  plugins: [],
};