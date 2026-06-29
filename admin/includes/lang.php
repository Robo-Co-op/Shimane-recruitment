<?php
require_once __DIR__ . '/../../includes/base.php';
// Set language via GET param (e.g. ?setlang=ja) and redirect back preserving other params
if (isset($_GET['setlang'])) {
    $l = in_array($_GET['setlang'], ['en','ja']) ? $_GET['setlang'] : 'en';
    setcookie('admin_lang', $l, time() + 60*60*24*365, '/');
    $params = $_GET; unset($params['setlang']);
    $path = strtok($_SERVER['REQUEST_URI'], '?');
    $back = $params ? $path . '?' . http_build_query($params) : $path;
    header('Location: ' . $back); exit;
}

function admin_lang(): string {
    return ($_COOKIE['admin_lang'] ?? 'en') === 'ja' ? 'ja' : 'en';
}

function t(string $key): string {
    static $strings = null;
    if ($strings === null) $strings = _admin_strings();
    $lang = admin_lang();
    return $strings[$lang][$key] ?? $strings['en'][$key] ?? $key;
}

function _admin_strings(): array { return [
'en' => [
    // General
    'sign_in'           => 'Sign In',
    'sign_out'          => 'Sign out',
    'save'              => 'Save',
    'cancel'            => 'Cancel',
    'delete'            => 'Delete',
    'edit'              => 'Edit',
    'view'              => 'View',
    'search'            => 'Search',
    'clear'             => 'Clear',
    'remove'            => 'Remove',
    'send'              => 'Send',
    'back'              => 'Back',
    'yes'               => 'Yes',
    'no'                => 'No',
    'loading'           => 'Loading…',
    'never'             => 'Never',
    'lang_en'           => 'EN',
    'lang_ja'           => 'JA',

    // Login
    'login_welcome'     => 'Welcome back',
    'login_subtitle'    => 'Sign in to the Shimane IB Admin.',
    'login_email'       => 'Email Address',
    'login_password'    => 'Password',
    'login_btn'         => 'Sign In →',
    'login_forgot'      => 'Forgot password?',
    'login_invalid'     => 'Invalid email or password.',
    'login_pending'     => 'Your account is pending. Please check your email for the invitation link.',
    'setup_title'       => 'Create Admin Account',
    'setup_subtitle'    => 'No admin accounts exist yet. Create the first one.',
    'setup_name'        => 'Full Name',
    'setup_btn'         => 'Create Admin Account →',

    // Forgot password
    'forgot_title'      => 'Forgot Password',
    'forgot_subtitle'   => 'Enter your email and we\'ll send you a reset link.',
    'forgot_btn'        => 'Send Reset Link →',
    'forgot_sent'       => 'If that email is registered, a reset link has been sent.',
    'back_to_login'     => '← Back to sign in',

    // Sidebar
    'nav_section'       => 'Navigation',
    'nav_dashboard'     => 'Dashboard',
    'nav_analytics'     => 'Analytics',
    'nav_submissions'   => 'Submissions',
    'nav_forms'         => 'Forms',
    'nav_content'       => 'Content',
    'nav_team'          => 'Team',
    'nav_live'          => 'Live Site',
    'nav_jp_page'       => 'JP Landing Page',
    'nav_en_page'       => 'EN Landing Page',
    'nav_apply'         => 'Application Form',
    'nav_settings'      => '⚙ Settings',

    // Dashboard
    'dash_title'        => 'Dashboard',
    'dash_total_apps'   => 'Total Applications',
    'dash_drafts'       => 'Incomplete Drafts',
    'dash_views_today'  => 'Page Views Today',
    'dash_apply_clicks' => 'Apply Button Clicks',
    'dash_complete_rate'=> 'Completion Rate',
    'dash_recent_apps'  => '🆕 Recent Applications',
    'dash_view_all'     => 'View all',
    'dash_incomplete'   => '⏳ Incomplete Forms',
    'dash_no_apps'      => 'No applications yet',
    'dash_no_apps_sub'  => 'Applications will appear here once people start submitting the form.',
    'dash_week_chart'   => '📊 Page Views — Last 7 Days',
    'dash_quick'        => '⚡ Quick Actions',
    'dash_unknown'      => 'Unknown',

    // Submissions
    'sub_title'         => 'Submissions',
    'sub_complete'      => '✅ Complete',
    'sub_in_progress'   => '⏳ In Progress',
    'sub_search'        => 'Search by name or email…',
    'sub_name'          => 'Name',
    'sub_email'         => 'Email',
    'sub_phone'         => 'Phone',
    'sub_lang'          => 'Language',
    'sub_support'       => 'Support',
    'sub_status'        => 'Status',
    'sub_date'          => 'Date',
    'sub_step'          => 'Step',
    'sub_started'       => 'Started',
    'sub_last_active'   => 'Last Active',
    'sub_reminded'      => 'Reminded',
    'sub_not_sent'      => 'Not sent',
    'sub_no_subs'       => 'No submissions found',
    'sub_no_drafts'     => 'No incomplete drafts',

    // Analytics
    'ana_title'         => 'Analytics',
    'ana_date_range'    => 'Date range:',
    'ana_views'         => 'Page Views',
    'ana_unique'        => 'Unique Visitors',
    'ana_apply_clicks'  => 'Apply Clicks',
    'ana_en_visitors'   => 'English Visitors',
    'ana_ja_visitors'   => 'Japanese Visitors',
    'ana_btn_clicks'    => 'Button Clicks',
    'ana_daily_chart'   => '📈 Daily Page Views',
    'ana_top_pages'     => '📄 Top Pages',
    'ana_lang_split'    => '🌍 Language Split',
    'ana_event_bdown'   => '🎯 Event Breakdown',

    // Team
    'team_title'        => 'Team Management',
    'team_members'      => '👥 Team Members',
    'team_invite'       => '✉️ Invite Team Member',
    'team_invite_note'  => 'An invitation email will be sent with a link to set their password.',
    'team_name'         => 'Full Name',
    'team_email'        => 'Email Address',
    'team_role'         => 'Role',
    'team_last_login'   => 'Last Login',
    'team_status'       => 'Status',
    'team_active'       => '✓ Active',
    'team_pending'      => '⏳ Pending',
    'team_send_invite'  => '✉️ Send Invitation',
    'team_resend'       => 'Resend',
    'team_access'       => '🔑 Access Levels',
    'team_you'          => 'You',
    'team_not_yet'      => 'Not yet',

    // Settings
    'set_title'         => 'My Settings',
    'set_profile'       => '👤 Profile',
    'set_full_name'     => 'Full Name',
    'set_email'         => 'Email Address',
    'set_save_profile'  => '💾 Save Profile',
    'set_account'       => 'ℹ️ Account Info',
    'set_role'          => 'Role',
    'set_created'       => 'Account created',
    'set_last_login'    => 'Last login',
    'set_this_session'  => 'This session',
    'set_change_pw'     => '🔒 Change Password',
    'set_current_pw'    => 'Current Password',
    'set_new_pw'        => 'New Password',
    'set_confirm_pw'    => 'Confirm New Password',
    'set_change_pw_btn' => '🔑 Change Password',
    'set_pw_min'        => 'Minimum 8 characters.',
],
'ja' => [
    // General
    'sign_in'           => 'サインイン',
    'sign_out'          => 'サインアウト',
    'save'              => '保存',
    'cancel'            => 'キャンセル',
    'delete'            => '削除',
    'edit'              => '編集',
    'view'              => '表示',
    'search'            => '検索',
    'clear'             => 'クリア',
    'remove'            => '削除',
    'send'              => '送信',
    'back'              => '戻る',
    'yes'               => 'はい',
    'no'                => 'いいえ',
    'loading'           => '読み込み中…',
    'never'             => 'なし',
    'lang_en'           => 'EN',
    'lang_ja'           => 'JA',

    // Login
    'login_welcome'     => 'おかえりなさい',
    'login_subtitle'    => '島根IB採用管理システムにサインイン',
    'login_email'       => 'メールアドレス',
    'login_password'    => 'パスワード',
    'login_btn'         => 'サインイン →',
    'login_forgot'      => 'パスワードをお忘れですか？',
    'login_invalid'     => 'メールアドレスまたはパスワードが正しくありません。',
    'login_pending'     => 'アカウントは保留中です。招待メールをご確認ください。',
    'setup_title'       => '管理者アカウントを作成',
    'setup_subtitle'    => '管理者アカウントがまだありません。最初のアカウントを作成してください。',
    'setup_name'        => '氏名',
    'setup_btn'         => '管理者アカウントを作成 →',

    // Forgot password
    'forgot_title'      => 'パスワードをお忘れの方',
    'forgot_subtitle'   => 'メールアドレスを入力してください。リセットリンクをお送りします。',
    'forgot_btn'        => 'リセットリンクを送信 →',
    'forgot_sent'       => 'そのメールアドレスが登録済みの場合、リセットリンクを送信しました。',
    'back_to_login'     => '← サインインに戻る',

    // Sidebar
    'nav_section'       => 'メニュー',
    'nav_dashboard'     => 'ダッシュボード',
    'nav_analytics'     => 'アナリティクス',
    'nav_submissions'   => '応募一覧',
    'nav_forms'         => 'フォーム',
    'nav_content'       => 'コンテンツ',
    'nav_team'          => 'チーム',
    'nav_live'          => 'ライブサイト',
    'nav_jp_page'       => 'JP ランディングページ',
    'nav_en_page'       => 'EN ランディングページ',
    'nav_apply'         => '応募フォーム',
    'nav_settings'      => '⚙ 設定',

    // Dashboard
    'dash_title'        => 'ダッシュボード',
    'dash_total_apps'   => '総応募数',
    'dash_drafts'       => '未完了の下書き',
    'dash_views_today'  => '本日のページ閲覧数',
    'dash_apply_clicks' => '応募ボタンのクリック数',
    'dash_complete_rate'=> '完了率',
    'dash_recent_apps'  => '🆕 最新の応募',
    'dash_view_all'     => 'すべて表示',
    'dash_incomplete'   => '⏳ 未完了フォーム',
    'dash_no_apps'      => 'まだ応募がありません',
    'dash_no_apps_sub'  => 'フォームが送信されると、ここに表示されます。',
    'dash_week_chart'   => '📊 過去7日間のページ閲覧数',
    'dash_quick'        => '⚡ クイックアクション',
    'dash_unknown'      => '不明',

    // Submissions
    'sub_title'         => '応募一覧',
    'sub_complete'      => '✅ 完了',
    'sub_in_progress'   => '⏳ 進行中',
    'sub_search'        => '名前またはメールで検索…',
    'sub_name'          => '氏名',
    'sub_email'         => 'メール',
    'sub_phone'         => '電話番号',
    'sub_lang'          => '言語',
    'sub_support'       => 'サポート',
    'sub_status'        => 'ステータス',
    'sub_date'          => '日付',
    'sub_step'          => 'ステップ',
    'sub_started'       => '開始日',
    'sub_last_active'   => '最終アクティブ',
    'sub_reminded'      => 'リマインダー',
    'sub_not_sent'      => '未送信',
    'sub_no_subs'       => '応募が見つかりません',
    'sub_no_drafts'     => '未完了の下書きはありません',

    // Analytics
    'ana_title'         => 'アナリティクス',
    'ana_date_range'    => '期間：',
    'ana_views'         => 'ページ閲覧数',
    'ana_unique'        => 'ユニーク訪問者',
    'ana_apply_clicks'  => '応募クリック数',
    'ana_en_visitors'   => '英語訪問者',
    'ana_ja_visitors'   => '日本語訪問者',
    'ana_btn_clicks'    => 'ボタンクリック数',
    'ana_daily_chart'   => '📈 日別ページ閲覧数',
    'ana_top_pages'     => '📄 人気ページ',
    'ana_lang_split'    => '🌍 言語別',
    'ana_event_bdown'   => '🎯 イベント内訳',

    // Team
    'team_title'        => 'チーム管理',
    'team_members'      => '👥 チームメンバー',
    'team_invite'       => '✉️ メンバーを招待',
    'team_invite_note'  => 'パスワード設定用の招待メールが送信されます。',
    'team_name'         => '氏名',
    'team_email'        => 'メールアドレス',
    'team_role'         => '権限',
    'team_last_login'   => '最終ログイン',
    'team_status'       => 'ステータス',
    'team_active'       => '✓ アクティブ',
    'team_pending'      => '⏳ 保留中',
    'team_send_invite'  => '✉️ 招待を送信',
    'team_resend'       => '再送信',
    'team_access'       => '🔑 アクセスレベル',
    'team_you'          => 'あなた',
    'team_not_yet'      => '未ログイン',

    // Settings
    'set_title'         => 'マイ設定',
    'set_profile'       => '👤 プロフィール',
    'set_full_name'     => '氏名',
    'set_email'         => 'メールアドレス',
    'set_save_profile'  => '💾 プロフィールを保存',
    'set_account'       => 'ℹ️ アカウント情報',
    'set_role'          => '権限',
    'set_created'       => 'アカウント作成日',
    'set_last_login'    => '最終ログイン',
    'set_this_session'  => '今回のセッション',
    'set_change_pw'     => '🔒 パスワード変更',
    'set_current_pw'    => '現在のパスワード',
    'set_new_pw'        => '新しいパスワード',
    'set_confirm_pw'    => '新しいパスワード（確認）',
    'set_change_pw_btn' => '🔑 パスワードを変更',
    'set_pw_min'        => '8文字以上で入力してください。',
],
]; }
