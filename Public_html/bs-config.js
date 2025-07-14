module.exports = {
  proxy: "http://localhost:3000",
  files: [
    "**/*.php",
    "**/*.css",
    "**/*.js"
  ],
  open: true,
  notify: false,
  startPath: "/login.php"
};