<?php

namespace App\Services;

class MaskAddressService
{
    /**
     * 住所をマスキング処理する
     * 例: "東京都渋谷区青山１－１－１" → "東京都渋谷区周辺"
     */
    public function maskAddress(string $fullAddress): string
    {
        if (empty($fullAddress)) {
            return '';
        }

        // 日本の都道府県リスト
        $prefectures = [
            '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
            '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
            '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
            '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
            '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
            '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
            '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
        ];

        // 都道府県名でマッチング（最長一致）
        $matchedPrefecture = null;
        foreach ($prefectures as $prefecture) {
            if (mb_strpos($fullAddress, $prefecture) === 0) {
                $matchedPrefecture = $prefecture;
                break;
            }
        }

        if ($matchedPrefecture) {
            // 都道府県が見つかった場合、市区町村までを抽出
            $remainingAddress = mb_substr($fullAddress, mb_strlen($matchedPrefecture));
            
            // 市区町村のパターンをチェック（例: 渋谷区、札幌市）
            if (preg_match('/^([市区町村]+[市区町村区])/', $remainingAddress, $matches)) {
                $city = $matches[1];
                return $matchedPrefecture . $city . '周辺';
            }
            
            // 市区町村が見つからない場合は都道府県のみ
            return $matchedPrefecture . '周辺';
        }

        // 郵便番号から都道府県を推測（簡易版）
        if (preg_match('/^\d{3}-?\d{4}/', $fullAddress)) {
            return '周辺地域';
        }

        // その他の場合は最初の6文字を抽出して「周辺」を付ける（マルチバイト対応）
        if (mb_strlen($fullAddress) > 0) {
            $firstPart = mb_substr($fullAddress, 0, min(6, mb_strlen($fullAddress)));
            return $firstPart . '周辺';
        }

        return '住所情報';
    }

    /**
     * 住所から都道府県を抽出する
     * 例: "東京都渋谷区青山１－１－１" → "東京都"
     */
    public function extractPrefecture(string $fullAddress): ?string
    {
        if (empty($fullAddress)) {
            return null;
        }

        // 日本の都道府県リスト
        $prefectures = [
            '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
            '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
            '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
            '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
            '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
            '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
            '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
        ];

        // 都道府県名でマッチング（最長一致、マルチバイト対応）
        foreach ($prefectures as $prefecture) {
            if (mb_strpos($fullAddress, $prefecture) === 0) {
                return $prefecture;
            }
        }

        return null;
    }
}






