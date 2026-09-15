import type { PlayKey } from '../_typings'

/** 站长任务状态（PlayVideoEnum::STATUS_*） */
export enum PlayTaskStatusEnum {
    PENDING = 0,
    GENERATING = 1,
    SUCCESS = 2,
    FAILED = 3,
    TIMEOUT = 4
}

/** 台词限制：与后端 PlayVideoEnum 一致（4.5 字/秒，上限 60 秒 ≈ 270 字，最多 12 句） */
export const SPEECH_RUNES_PER_SECOND = 4.5
export const MAX_SPEECH_SECONDS = 60
export const MAX_LINES = 12
export const MAX_SPEECH_RUNES = 30
/** 口播字数下限（与站长 PlayVideoEnum::MIN_SPEECH_RUNES 一致）：对口型要求配音至少 1 秒，按 4.5 字/秒留余量 */
export const MIN_SPEECH_RUNES = 6

/** 上传限制（MB） */
export const FACE_IMAGE_MAX_MB = 10
export const CLIP_VIDEO_MAX_MB = 200
/** 用户视频时长上限（与站长 CLIP_PRODUCT_MAX_SEC / 中台一致）：成片=切片+整段用户视频，按秒计费 */
export const CLIP_VIDEO_MAX_SEC = 90
export const FACE_IMAGE_ACCEPT = '.jpg,.jpeg,.png,.webp'
export const CLIP_VIDEO_ACCEPT = '.mp4,.mov'

/** 生成浮层：轮询节奏与预期耗时（秒） */
export const POLL_INTERVAL_MS = 5000
export const POLL_BACKOFF_MS = 8000
export const PERCENT_CAP = 92
export const EXPECT_SECONDS: Record<PlayKey, number> = {
    face: 200,
    dance: 150,
    clip: 180
}

export const PLAYS: {
    key: PlayKey
    name: string
    icon: string
    desc: string
    live: boolean
}[] = [
    {
        key: 'face',
        name: '影视换脸改词',
        icon: 'pv_venetian_mask',
        desc: '经典片段说你的词',
        live: true
    },
    {
        key: 'dance',
        name: '跳舞换脸',
        icon: 'pv_music_4',
        desc: '热门舞蹈换你出镜',
        live: true
    },
    {
        key: 'clip',
        name: '影视引流拼接',
        icon: 'pv_film',
        desc: '切片开场引爆完播',
        live: true
    }
]

export interface PlayIntro {
    note: string
    steps: { icon: string; fg: string; bg: string; t: string; d: string }[]
    how: string[]
    tip: string
}

/** 玩法介绍（文案对齐原型 INTRO） */
export const PLAY_INTRO: Record<PlayKey, PlayIntro> = {
    face: {
        note: '拉的是：反差感 & 记忆点',
        steps: [
            {
                icon: 'pv_clapperboard',
                fg: '#F59E0B',
                bg: '#FFF3E0',
                t: '经典影视片段',
                d: '台词深入人心，自带传播基因'
            },
            {
                icon: 'pv_venetian_mask',
                fg: '#2680F7',
                bg: '#E3EEFF',
                t: '换脸 + 改词',
                d: '脸换成你，词改成你的产品'
            },
            {
                icon: 'pv_message_circle_heart',
                fg: '#16A34A',
                bg: '#E8F8EF',
                t: '评论区裂变',
                d: '「这也行？」互动评论拉权重'
            }
        ],
        how: [
            '挑一个经典对白片段',
            '上传你的正脸照',
            '台本逐句改写成你的词',
            '选配音音色，生成'
        ],
        tip: '为什么有效：观众对经典台词有肌肉记忆，你把词换成自家产品的瞬间会产生强烈反差，这种「意料之外」是评论区互动的主要来源，而互动权重直接影响推荐。'
    },
    dance: {
        note: '拉的是：模仿门槛低 & BGM 加成',
        steps: [
            {
                icon: 'pv_flame',
                fg: '#F59E0B',
                bg: '#FFF3E0',
                t: '热门舞蹈模板',
                d: 'BGM 正在风口，蹭热度自然流'
            },
            {
                icon: 'pv_user_round_check',
                fg: '#2680F7',
                bg: '#E3EEFF',
                t: '换脸出镜',
                d: '不用会跳，脸换成你就行'
            },
            {
                icon: 'pv_store',
                fg: '#16A34A',
                bg: '#E8F8EF',
                t: '人设引流',
                d: '片尾口播导关注/进店'
            }
        ],
        how: [
            '挑一个正在热的舞蹈模板',
            '上传你的正脸照',
            '可选换掉背景为你的门店/场景',
            '开片尾口播引导，生成'
        ],
        tip: '为什么有效：热门 BGM 本身带着流量池，平台会把使用同一音乐的内容推给同一批人。你不需要会跳，换脸就能加入这波流量，还能把背景换成自己的门店做场景植入。'
    },
    clip: {
        note: '拉的是：完播率 & 停留时长',
        steps: [
            {
                icon: 'pv_popcorn',
                fg: '#F59E0B',
                bg: '#FFF3E0',
                t: '吸睛切片开场',
                d: '名场面 10~20s，观众停留看完'
            },
            {
                icon: 'pv_zap',
                fg: '#2680F7',
                bg: '#E3EEFF',
                t: '突然无缝拼接',
                d: '正上头时硬切进你的产品段'
            },
            {
                icon: 'pv_trending_up',
                fg: '#16A34A',
                bg: '#E8F8EF',
                t: '流量承接',
                d: '完播/停留拉高，推荐池给量'
            }
        ],
        how: [
            '挑一个吸睛切片模板',
            '上传一条你自己的视频',
            '设置切片保留时长与转场，生成'
        ],
        tip: '为什么有效：平台把完播率和停留时长当成核心推荐指标。影视名场面能在前 3 秒留住人，看完切片正处情绪高点时切进你的内容，完播数据就赢了大半。'
    }
}

export const LEGAL_TEXT: Record<PlayKey, string> = {
    face: '换脸仅支持本人授权的人脸照片。请勿使用他人肖像，侵权责任由使用者自行承担。',
    dance: '换脸仅支持本人授权的人脸照片。舞蹈模板与 BGM 均来自平台授权曲库。',
    clip: '切片素材来自平台授权片库；请确认上传的视频为你本人拥有或已获授权的内容，侵权责任由使用者自行承担。'
}

export const WALL_TITLE: Record<PlayKey, string> = {
    face: '选一个影视片段',
    dance: '选一个舞蹈模板',
    clip: '选一个吸睛切片'
}
export const WALL_SUB: Record<PlayKey, string> = {
    face: '经典对白场面，换脸并改写台词',
    dance: '自带爆款 BGM，换脸出镜',
    clip: '作为视频开头，留住观众的那 10~20 秒'
}

export const VOICES = [
    '相似音色',
    '清亮女声',
    '温柔女声',
    '磁性男声',
    '沉稳大叔'
]

export const KEEP_OPTIONS = [10, 15, 20]

/** 站长在模板已下架时返回的提示关键字（PlayVideoLogic::submit），命中就回模板墙重选 */
export const TEMPLATE_OFFLINE_HINT = '已下架'

export const TRANSITIONS = [
    { key: 'none', name: '硬切', desc: '干脆利落' },
    { key: 'fade', name: '叠化', desc: '柔和过渡' },
    { key: 'flash', name: '闪切', desc: '模糊冲刺' }
] as const
export type TransitionKey = (typeof TRANSITIONS)[number]['key']
