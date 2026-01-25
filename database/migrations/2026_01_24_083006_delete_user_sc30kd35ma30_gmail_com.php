<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $email = 'sc30kd35ma30@gmail.com';
        
        echo "========================================\n";
        echo "ユーザー削除マイグレーション開始\n";
        echo "対象メールアドレス: {$email}\n";
        echo "========================================\n\n";
        
        // ユーザーを検索
        $user = DB::table('users')->where('email', $email)->first();
        
        if (!$user) {
            // ユーザーが見つからない場合は何もしない
            echo "⚠️  ユーザー {$email} が見つかりませんでした。\n";
            echo "マイグレーションをスキップします。\n";
            return;
        }
        
        $userId = $user->id;
        $userName = $user->name;
        $userRole = $user->role;
        
        echo "📋 削除対象ユーザー情報:\n";
        echo "   ID: {$userId}\n";
        echo "   メールアドレス: {$email}\n";
        echo "   名前: {$userName}\n";
        echo "   ロール: {$userRole}\n";
        echo "   作成日時: {$user->created_at}\n\n";
        
        // 削除される関連データの確認
        echo "📊 削除される関連データの確認:\n";
        
        $userProfilesCount = DB::table('user_profiles')->where('user_id', $userId)->count();
        $guideProfilesCount = DB::table('guide_profiles')->where('user_id', $userId)->count();
        $requestsCount = DB::table('requests')->where('user_id', $userId)->count();
        $guideAcceptancesCount = DB::table('guide_acceptances')->where('guide_id', $userId)->count();
        $matchingsAsUserCount = DB::table('matchings')->where('user_id', $userId)->count();
        $matchingsAsGuideCount = DB::table('matchings')->where('guide_id', $userId)->count();
        $chatMessagesCount = DB::table('chat_messages')->where('sender_id', $userId)->count();
        $reportsAsGuideCount = DB::table('reports')->where('guide_id', $userId)->count();
        $reportsAsUserCount = DB::table('reports')->where('user_id', $userId)->count();
        $notificationsCount = DB::table('notifications')->where('user_id', $userId)->count();
        $userMonthlyLimitsCount = DB::table('user_monthly_limits')->where('user_id', $userId)->count();
        $announcementReadsCount = DB::table('announcement_reads')->where('user_id', $userId)->count();
        $announcementsCreatedCount = DB::table('announcements')->where('created_by', $userId)->count();
        $nominatedRequestsCount = DB::table('requests')->where('nominated_guide_id', $userId)->count();
        
        echo "   - user_profiles: {$userProfilesCount} 件\n";
        echo "   - guide_profiles: {$guideProfilesCount} 件\n";
        echo "   - requests (作成): {$requestsCount} 件\n";
        echo "   - requests (指名ガイド): {$nominatedRequestsCount} 件 (nominated_guide_idはNULLになります)\n";
        echo "   - guide_acceptances: {$guideAcceptancesCount} 件\n";
        echo "   - matchings (ユーザーとして): {$matchingsAsUserCount} 件\n";
        echo "   - matchings (ガイドとして): {$matchingsAsGuideCount} 件\n";
        echo "   - chat_messages: {$chatMessagesCount} 件\n";
        echo "   - reports (ガイドとして): {$reportsAsGuideCount} 件\n";
        echo "   - reports (ユーザーとして): {$reportsAsUserCount} 件\n";
        echo "   - notifications: {$notificationsCount} 件\n";
        echo "   - user_monthly_limits: {$userMonthlyLimitsCount} 件\n";
        echo "   - announcement_reads: {$announcementReadsCount} 件\n";
        echo "   - announcements (作成): {$announcementsCreatedCount} 件\n\n";
        
        $totalRecords = $userProfilesCount + $guideProfilesCount + $requestsCount + 
                       $guideAcceptancesCount + 
                       $matchingsAsUserCount + $matchingsAsGuideCount + 
                       $chatMessagesCount + $reportsAsGuideCount + $reportsAsUserCount + 
                       $notificationsCount + $userMonthlyLimitsCount + $announcementReadsCount + 
                       $announcementsCreatedCount;
        
        echo "   合計削除レコード数: {$totalRecords} 件 (usersテーブルの1件を含む)\n\n";
        
        echo "⚠️  警告: この操作は不可逆です。\n";
        echo "続行しますか？\n\n";
        
        // このユーザーが作成したannouncementがある場合、created_byを管理者に変更
        // まず管理者を取得（存在する場合）
        $admin = DB::table('users')
            ->where('role', 'admin')
            ->orderBy('id')
            ->first();
        
        if ($announcementsCreatedCount > 0) {
            if ($admin) {
                // 管理者が存在する場合、このユーザーが作成したannouncementのcreated_byを管理者に変更
                echo "📝 announcementsのcreated_byを管理者に変更中...\n";
                $updatedCount = DB::table('announcements')
                    ->where('created_by', $userId)
                    ->update(['created_by' => $admin->id]);
                
                if ($updatedCount > 0) {
                    echo "   ✅ {$updatedCount} 件のannouncementのcreated_byを管理者 (ID: {$admin->id}) に変更しました。\n\n";
                }
            } else {
                // 管理者が存在しない場合、このユーザーが作成したannouncementを削除
                echo "📝 announcementsを削除中（管理者が存在しないため）...\n";
                $deletedReadsCount = DB::table('announcement_reads')
                    ->whereIn('announcement_id', function($query) use ($userId) {
                        $query->select('id')
                            ->from('announcements')
                            ->where('created_by', $userId);
                    })
                    ->delete();
                
                $deletedAnnouncements = DB::table('announcements')
                    ->where('created_by', $userId)
                    ->delete();
                
                if ($deletedAnnouncements > 0) {
                    echo "   ✅ {$deletedAnnouncements} 件のannouncementと{$deletedReadsCount} 件のannouncement_readsを削除しました。\n\n";
                }
            }
        }
        
        // nominated_guide_idをNULLに設定（SET NULL制約により自動的に行われるが、明示的に処理）
        if ($nominatedRequestsCount > 0) {
            echo "📝 requestsのnominated_guide_idをNULLに設定中...\n";
            DB::table('requests')
                ->where('nominated_guide_id', $userId)
                ->update(['nominated_guide_id' => null]);
            echo "   ✅ {$nominatedRequestsCount} 件のrequestsのnominated_guide_idをNULLに設定しました。\n\n";
        }
        
        // ユーザーを削除（CASCADEで関連データも自動削除される）
        echo "🗑️  ユーザーを削除中...\n";
        echo "   （CASCADEにより関連データも自動削除されます）\n";
        
        $deleted = DB::table('users')->where('id', $userId)->delete();
        
        if ($deleted) {
            echo "\n✅ ユーザー {$email} (ID: {$userId}) を削除しました。\n";
            echo "   関連する {$totalRecords} 件のレコードも削除されました。\n";
            echo "\n========================================\n";
            echo "マイグレーション完了\n";
            echo "========================================\n";
        } else {
            echo "\n❌ ユーザー {$email} の削除に失敗しました。\n";
            echo "   データベースの状態を確認してください。\n";
            throw new \Exception("ユーザー削除に失敗しました");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 削除操作は元に戻せないため、何もしない
        echo "このマイグレーションは元に戻せません（ユーザー削除は不可逆操作です）。\n";
    }
};
