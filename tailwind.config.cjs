/** Build configuration for production utility CSS. */
module.exports = {
  content: ['./*.php', './api/**/*.php', './api/**/*.js'],
  theme: {
    extend: {
      colors: {
        brand: {
          bg: '#f5f8f5',
          primary: '#176b4c',
          primaryHover: '#0e4c37',
          secondary: '#b7e7c3',
          text: '#17352c',
          muted: '#64766e',
          card: '#fff'
        }
      }
    }
  },
  plugins: []
};
