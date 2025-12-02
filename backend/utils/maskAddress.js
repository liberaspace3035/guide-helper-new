// 住所マスキング処理ユーティリティ
// 詳細な住所をマスキングして、ガイドには大まかな地域情報のみを表示

/**
 * 住所をマスキング処理する
 * 例: "東京都渋谷区青山１－１－１" → "東京都渋谷区周辺"
 * @param {string} fullAddress - 完全な住所
 * @returns {string} - マスキングされた住所
 */
function maskAddress(fullAddress) {
  if (!fullAddress || typeof fullAddress !== 'string') {
    return '';
  }

  // 都道府県、市区町村までを抽出
  // パターン1: 都道府県 + 市区町村（例: 東京都渋谷区）
  const prefectureCityMatch = fullAddress.match(/^([都道府県]+[都道府県]|[都道府県]+)([市区町村]+[市区町村区])/);
  
  if (prefectureCityMatch) {
    const prefecture = prefectureCityMatch[1];
    const city = prefectureCityMatch[2];
    return `${prefecture}${city}周辺`;
  }

  // パターン2: 都道府県のみ（例: 大阪府）
  const prefectureMatch = fullAddress.match(/^([都道府県]+[都道府県]|[都道府県]+)/);
  if (prefectureMatch) {
    return `${prefectureMatch[1]}周辺`;
  }

  // パターン3: 郵便番号から都道府県を推測（簡易版）
  const zipMatch = fullAddress.match(/^\d{3}-?\d{4}/);
  if (zipMatch) {
    // 郵便番号から都道府県を推測する場合は、外部APIやデータベースが必要
    // ここでは簡易的に「周辺」を返す
    return '周辺地域';
  }

  // その他の場合は最初の2-3文字を抽出して「周辺」を付ける
  if (fullAddress.length > 0) {
    const firstPart = fullAddress.substring(0, Math.min(6, fullAddress.length));
    return `${firstPart}周辺`;
  }

  return '住所情報';
}

module.exports = { maskAddress };

