module.exports = {
  testEnvironment: 'jsdom',
  testMatch: ['**/tests/js/**/*.test.js'],
  setupFilesAfterEnv: ['<rootDir>/tests/js/setup.js'],
  transform: {
    '^.+\\.jsx?$': 'babel-jest',
  },
};
