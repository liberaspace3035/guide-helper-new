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

        // 都道府県、市区町村までを抽出
        // パターン1: 都道府県 + 市区町村（例: 東京都渋谷区）
        if (preg_match('/^([都道府県]+[都道府県]|[都道府県]+)([市区町村]+[市区町村区])/', $fullAddress, $matches)) {
            $prefecture = $matches[1];
            $city = $matches[2];
            return $prefecture . $city . '周辺';
        }

        // パターン2: 都道府県のみ（例: 大阪府）
        if (preg_match('/^([都道府県]+[都道府県]|[都道府県]+)/', $fullAddress, $matches)) {
            return $matches[1] . '周辺';
        }

        // パターン3: 郵便番号から都道府県を推測（簡易版）
        if (preg_match('/^\d{3}-?\d{4}/', $fullAddress)) {
            return '周辺地域';
        }

        // その他の場合は最初の2-3文字を抽出して「周辺」を付ける
        if (strlen($fullAddress) > 0) {
            $firstPart = substr($fullAddress, 0, min(6, strlen($fullAddress)));
            return $firstPart . '周辺';
        }

        return '住所情報';
    }
}






