// JWT認証ミドルウェア
const jwt = require('jsonwebtoken');

// トークン検証ミドルウェア
const authenticateToken = (req, res, next) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1]; // Bearer TOKEN

  if (!token) {
    return res.status(401).json({ error: '認証トークンが提供されていません' });
  }

  jwt.verify(token, process.env.JWT_SECRET, (err, user) => {
    if (err) {
      return res.status(403).json({ error: '無効な認証トークンです' });
    }
    req.user = user;
    next();
  });
};

// ロールベースのアクセス制御
const requireRole = (...roles) => {
  return (req, res, next) => {
    if (!req.user) {
      return res.status(401).json({ error: '認証が必要です' });
    }

    if (!roles.includes(req.user.role)) {
      return res.status(403).json({ error: 'この操作を実行する権限がありません' });
    }

    next();
  };
};

module.exports = {
  authenticateToken,
  requireRole
};

