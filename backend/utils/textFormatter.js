// AIテキスト整形ユーティリティ
// 音声入力やユーザー入力テキストを読みやすく整形

/**
 * テキストを整形して読みやすくする
 * @param {string} text - 整形前のテキスト
 * @returns {string} - 整形後のテキスト
 */
function formatText(text) {
  if (!text || typeof text !== 'string') {
    return '';
  }

  let formatted = text;

  // 1. 余分な空白を削除
  formatted = formatted.replace(/\s+/g, ' ').trim();

  // 2. 句読点の後にスペースを追加（ない場合）
  formatted = formatted.replace(/([。、])([^\s])/g, '$1 $2');

  // 3. 改行を適切に処理
  formatted = formatted.replace(/\n{3,}/g, '\n\n');

  // 4. 数字と単位の間にスペースを追加（読みやすくするため）
  formatted = formatted.replace(/(\d+)([年月日時分秒])/g, '$1$2');

  // 5. カタカナの連続を適切に処理
  formatted = formatted.replace(/([ァ-ヶー]+)([あ-ん])/g, '$1 $2');

  // 6. 英語と日本語の間にスペースを追加
  formatted = formatted.replace(/([a-zA-Z0-9]+)([あ-んァ-ヶー一-龠])/g, '$1 $2');
  formatted = formatted.replace(/([あ-んァ-ヶー一-龠])([a-zA-Z0-9]+)/g, '$1 $2');

  return formatted.trim();
}

/**
 * 音声入力テキストを整形（より積極的な整形）
 * @param {string} text - 音声入力テキスト
 * @returns {string} - 整形後のテキスト
 */
function formatVoiceText(text) {
  if (!text || typeof text !== 'string') {
    return '';
  }

  let formatted = formatText(text);

  // 音声入力特有の処理
  // 1. よくある誤認識を修正
  const corrections = {
    'えー': '',
    'あー': '',
    'うーん': '',
    'まあ': '',
    'えっと': ''
  };

  Object.keys(corrections).forEach(key => {
    const regex = new RegExp(key, 'g');
    formatted = formatted.replace(regex, corrections[key]);
  });

  // 2. 連続する同じ文字を削減（例: "あああ" → "ああ"）
  formatted = formatted.replace(/(.)\1{2,}/g, '$1$1');

  return formatted.trim();
}

module.exports = {
  formatText,
  formatVoiceText
};

