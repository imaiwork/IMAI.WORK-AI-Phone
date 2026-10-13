// 首页所有 popup-bottom 弹窗的 activeModal 标识；禁止再在比较 / 赋值里用字符串字面量（见 AGENTS.md §10）
export enum HomeModalType {
    HOT = 'hot',
    CREATE = 'create',
    PUBLISH = 'publish',
    RIVAL_INTERNET = 'rival-internet',
    RIVAL_LOCAL = 'rival-local',
    STORE_TARGET = 'store-target',
    WX_VIDEO_LEAD = 'wxvideo-lead',
    CHAT_SOCIAL = 'chat-social',
    CHAT_COMMENT = 'chat-comment',
    CHAT_WECHAT = 'chat-wechat',
    CHAT_MOMENTS = 'chat-moments'
}
