/**
 * @description 智能体数据结构
 */
export interface Agent {
    id: string | number; // 唯一标识
    name: string; // 智能体名称
    kb_type: 1 | 2; // 知识库类型
    kb_ids: string[] | number[] | string | number; // 挂靠的知识库ID列表
    icons: string; // 图标
    image: string; // 封面图URL
    bg_image: string; // 背景图URL
    intro: string; // 简介
    model_id: string | number; // 主模型ID
    model_sub_id: string | number; // 子模型ID
    roles_prompt: string; // 角色设定提示词

    // --- 知识库相关设置 ---
    search_mode: string; // 检索模式: [similar=语义检索, full=全文检索, mix=混合检索]
    search_tokens: number; // 检索引用tokens范围: [0-30000]
    search_similar: number; // 检索相似度: [0 ~ 1]
    ranking_status: number; // 重排状态 (0: 关闭, 1: 开启)
    ranking_score: number; // 重排分数
    context_num: number; // 上下文数量

    // --- 问题优化设置 ---
    optimize_ask: number; // 问题优化开关 (0: 关, 1: 开)
    optimize_m_id: string | number; // 优化用的主模型ID
    optimize_s_id: string | number; // 优化用的子模型ID
    search_empty_type: number; // 空搜索回复类型 (1: AI回复, 2: 自定义回复)
    search_empty_text: string; // 空搜索时的自定义回复内容

    // --- 拟人化设置 ---
    top_p: number; // 词汇多样性 (0.01-1)
    temperature: number; // 结果相似性 (0-2)
    presence_penalty: number; // 特定词重复率 (-2-2)
    frequency_penalty: number; // 重复词频率 (-2-2)
    top_logprobs: number; // 显示前几个候选词对数概率(0到20)
    logprobs: number; // 显示候选词 0关闭 1开启
    // --- 界面配置 ---
    welcome_introducer: string; // 欢迎语
    copyright: string; // 底部标识
    menus: {
        images: string[]; // 菜单项图片
        content: string; // 菜单项回复内容
        keyword: string; // 菜单项关键词
    }[];

    // --- 其他 ---
    is_public: number; // 是否公开 (0: 否, 1: 是)
    is_enable: number; // 是否可用 (0: 否, 1: 是)
    flow_status: number; // 工作流状态 (0: 关闭, 1: 开启)
    flow_config: {
        workflow_id: string; // 工作流ID
        bot_id: string; // 智能体ID
        app_id: string; // 应用ID
        api_token: string; // 授权Token
    };
    cate_id: string | number; // 类目ID
    threshold: number; // 技能阈值
    mode_type: ModeTypeEnum; // 1=自定义,2=平衡模式,3=精准模式,4=创意模式
}

export enum ModeTypeEnum {
    CUSTOM = 1,
    BALANCE = 2,
    PRECISE = 3,
    CREATIVE = 4,
}

/**
 * @description 智能体类型枚举
 */
export enum AgentTypeEnum {
    AGENT = 0, // 标准智能体
    COZE_AGENT = 1, // Coze智能体
    COZE_FLOW = 2, // Coze工作流
}

/**
 * @description 发布渠道类型枚举
 */
export enum PublishTypeEnum {
    WEB = 1, // 网页
    JS = 2, // JS嵌入
    GZH = 3, // 微信公众号
    POSTER = 4, // 海报
    API = 5, // API调用
}

/**
 * @description Coze对象类型枚举
 */
export enum CozeTypeEnum {
    AGENT = 1, // Coze智能体
    FLOW = 2, // Coze工作流
}

/**
 * @description Coze工作流表单字段类型枚举
 */
export enum FormFieldTypeEnum {
    INPUT = "input",
    TEXTAREA = "textarea",
    NUMBER = "number",
    VIDEO = "video",
    IMAGE = "image",
    FILE = "file",
}
