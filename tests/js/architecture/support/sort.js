// Byte/code-unit string order — matches PHP `ksort( …, SORT_STRING )` used by the PHP baseline.
function compareCodeUnits(a, b) {
  if (a < b) return -1;
  if (a > b) return 1;
  return 0;
}

module.exports = { compareCodeUnits };