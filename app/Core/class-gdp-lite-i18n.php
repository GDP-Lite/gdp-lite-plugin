<?php
/**
 * Lightweight English and Simplified Chinese translations.
 *
 * @package GDPLite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GDP_Lite_I18n {
	/** @var GDP_Lite_I18n|null */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return GDP_Lite_I18n
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register translation filters.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'gettext', array( $this, 'translate' ), 10, 3 );
		add_filter( 'ngettext', array( $this, 'translate_plural' ), 10, 5 );
	}

	/**
	 * Get selected language.
	 *
	 * @return string
	 */
	public function get_language() {
		$language = get_option( 'gdp_lite_language', 'auto' );
		if ( 'zh_CN' === $language || 'en_US' === $language ) {
			return $language;
		}

		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		return 0 === strpos( strtolower( $locale ), 'zh' ) ? 'zh_CN' : 'en_US';
	}

	/**
	 * Translate a plugin string.
	 *
	 * @param string $translation Existing translation.
	 * @param string $text        Original string.
	 * @param string $domain      Text domain.
	 * @return string
	 */
	public function translate( $translation, $text, $domain ) {
		if ( 'gdp-lite' !== $domain || 'zh_CN' !== $this->get_language() ) {
			return $translation;
		}

		$translations = $this->translations();
		return isset( $translations[ $text ] ) ? $translations[ $text ] : $translation;
	}

	/**
	 * Translate plural strings.
	 *
	 * @param string $translation Existing translation.
	 * @param string $single      Singular string.
	 * @param string $plural      Plural string.
	 * @param int    $number      Number.
	 * @param string $domain      Text domain.
	 * @return string
	 */
	public function translate_plural( $translation, $single, $plural, $number, $domain ) {
		if ( 'gdp-lite' !== $domain || 'zh_CN' !== $this->get_language() ) {
			return $translation;
		}

		$translations = $this->translations();
		return isset( $translations[ $single ] ) ? $translations[ $single ] : $translation;
	}

	/**
	 * Translation dictionary.
	 *
	 * @return array<string,string>
	 */
	private function translations() {
		return array(
			'Dashboard' => '仪表盘',
			'All Games' => '全部游戏',
			'Add New Game' => '添加新游戏',
			'Add New' => '添加新游戏',
			'Game Types' => '游戏类型',
			'Game Type' => '游戏类型',
			'Providers' => '游戏商',
			'Provider' => '游戏商',
			'Collections' => '合集',
			'Collection' => '合集',
			'Categories' => '分类',
			'Category' => '分类',
			'Import' => '导入',
			'Settings' => '设置',
			'PROFESSIONAL ADMIN UI' => '专业后台管理',
			'GDP Lite' => 'GDP Lite',
			'Lightweight gaming manager for WordPress.' => '轻量级 WordPress 游戏数据管理插件。',
			'Games' => '游戏',
			'Game' => '游戏',
			'Version' => '版本',
			'WORKFLOW' => '工作流程',
			'Quick Actions' => '快捷操作',
			'Add Game' => '添加游戏',
			'Create a new game' => '创建一个新游戏',
			'Import or update JSON data' => '导入或更新 JSON 数据',
			'Manage game providers' => '管理游戏商',
			'Build reusable groups' => '创建可复用的游戏合集',
			'Configure default display' => '配置默认显示方式',
			'DISPLAY' => '显示',
			'Shortcodes' => '短代码',
			'Slots' => '老虎机',
			'Copy' => '复制',
			'Copied' => '已复制',
			'CONTENT' => '内容',
			'Recently Updated Games' => '最近更新的游戏',
			'View all' => '查看全部',
			'SYSTEM' => '系统',
			'Environment' => '运行环境',
			'Theme' => '主题',
			'No games yet' => '还没有游戏',
			'Add your first game or import JSON data to get started.' => '添加第一个游戏或导入 JSON 数据即可开始。',
			'Status' => '状态',
			'Updated' => '更新时间',
			'Published' => '已发布',
			'Draft' => '草稿',
			'CONFIGURATION' => '配置',
			'GDP Lite Settings' => 'GDP Lite 设置',
			'Set the default front-end display behaviour.' => '设置前端默认显示方式和后台语言。',
			'Settings saved.' => '设置已保存。',
			'Default columns' => '默认列数',
			'Used when the shortcode does not include a columns value.' => '当短代码没有指定 columns 参数时使用。',
			'Front-end corner style' => '前台圆角样式',
			'Rounded corners (10px)' => '圆角（10px）',
			'Square corners (0px)' => '直角（0px）',
			'Controls the corners of front-end game cards, labels and buttons.' => '控制前台游戏卡片、标签和按钮的圆角样式。',
			'Language' => '语言',
			'Auto (WordPress)' => '自动（跟随 WordPress）',
			'English' => '英语',
			'Simplified Chinese' => '简体中文',
			'Choose the language used inside GDP Lite admin pages.' => '选择 GDP Lite 后台页面使用的语言。',
			'Save Settings' => '保存设置',
			'Image' => '图片',
			'Type' => '类型',
			'Volatility' => '波动率',
			'Edit Game' => '编辑游戏',
			'Game Data' => '游戏数据',
			'Game data sections' => '游戏数据区域',
			'Basic' => '基础信息',
			'Gameplay' => '游戏参数',
			'Links & Display' => '链接与显示',
			'Features' => '功能特性',
			'Game Classification' => '游戏分类',
			'Assign the game type, provider, category and reusable collections.' => '设置游戏类型、游戏商、分类和可复用合集。',
			'Release Date' => '发布日期',
			'Maximum Win' => '最高倍数',
			'Gameplay Information' => '游戏参数',
			'Store the main slot statistics used in cards, tables and filters.' => '保存卡片、表格和筛选器需要的主要老虎机参数。',
			'RTP (%)' => 'RTP（%）',
			'Select volatility' => '选择波动率',
			'Low' => '低',
			'Medium' => '中',
			'High' => '高',
			'Reels' => '转轴',
			'Rows' => '行数',
			'Paylines / Ways' => '赔付线 / 方式',
			'Minimum Bet' => '最低投注',
			'Maximum Bet' => '最高投注',
			'Links & Card Display' => '链接与卡片显示',
			'Control game destinations and the front-end card label.' => '控制游戏跳转链接和前端卡片文字。',
			'Demo URL' => '试玩链接',
			'Play URL' => '游戏链接',
			'Badge' => '标签',
			'Button Text' => '按钮文字',
			'Game Features' => '游戏功能',
			'Select the mechanics available in this slot game.' => '选择该老虎机包含的功能机制。',
			'Select' => '请选择',
			'Manage options' => '管理选项',
			'Wild' => '百搭符号',
			'Scatter' => '分散符号',
			'Free Spins' => '免费旋转',
			'Bonus Buy' => '购买奖励',
			'Jackpot' => '累积大奖',
			'Multiplier' => '倍数',
			'Megaways' => 'Megaways',
			'Cascading Reels' => '连消转轴',
			'BULK CONTENT' => '批量内容',
			'Import Games' => '导入游戏',
			'Paste a JSON array to create new games or update existing games with the same slug.' => '粘贴 JSON 数组以创建新游戏，或更新具有相同 Slug 的现有游戏。',
			'%d game imported or updated.' => '已导入或更新 %d 个游戏。',
			'%d games imported or updated.' => '已导入或更新 %d 个游戏。',
			'Import JSON' => '导入 JSON',
			'Permission denied.' => '权限不足。',
			'View Game' => '查看游戏',
			'Getting Started' => '开始使用',
			'Create a provider, add a game, then display it with the shortcode.' => '先创建游戏商，再添加游戏，最后使用短代码显示游戏。',
			'Create Provider' => '创建游戏商',
			'Create Collection' => '创建合集',
			'Display Games' => '显示游戏',
			'Admin Language' => '后台语言',
			'Appearance' => '外观',
			'Permalinks' => '固定链接',
			'DESIGN SYSTEM' => '设计系统',
			'Control the front-end game grid without editing CSS.' => '无需修改 CSS，即可控制前端游戏网格样式。',
			'Appearance saved.' => '外观设置已保存。',
			'Card radius' => '卡片圆角',
			'Shadow' => '阴影',
			'Container width' => '容器宽度',
			'Image ratio' => '图片比例',
			'Button style' => '按钮样式',
			'Hover effect' => '悬停效果',
			'Grid gap' => '网格间距',
			'Save Appearance' => '保存外观',
			'None' => '无',
			'Light' => '轻微',
			'Medium' => '中等',
			'Strong' => '强烈',
			'Full Width' => '全宽',
			'Filled' => '填充',
			'Outline' => '描边',
			'Soft' => '柔和',
			'Lift' => '上浮',
			'Scale' => '缩放',
			'URL STRUCTURE' => 'URL 结构',
			'Use readable, conflict-resistant URL bases for GDP content.' => '为 GDP 内容设置清晰且不易冲突的 URL 路径。',
			'Permalinks saved and rewrite rules refreshed.' => '固定链接已保存，重写规则已刷新。',
			'Game Base' => '单个游戏路径',
			'Game Type Base' => '游戏类型路径',
			'Provider Base' => '游戏商路径',
			'Collection Base' => '游戏合集路径',
			'Save Permalinks' => '保存固定链接',
			'Each permalink base must be unique.' => '每个固定链接路径必须唯一。',
			'Set general plugin behaviour and language.' => '设置插件通用行为和后台语言。',
			'Featured Game' => '推荐游戏',
			'Show this game in featured queries.' => '在推荐游戏查询中显示此游戏。',
		);
	}
}
