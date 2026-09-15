import FilmBlueIcon from '@/ai_modules/digital_human/static/icons/play_video/film-blue.svg'
import FilmGrayIcon from '@/ai_modules/digital_human/static/icons/play_video/film-gray.svg'
import VenetianMaskBlueIcon from '@/ai_modules/digital_human/static/icons/play_video/venetian-mask-blue.svg'
import VenetianMaskGrayIcon from '@/ai_modules/digital_human/static/icons/play_video/venetian-mask-gray.svg'
import Music4BlueIcon from '@/ai_modules/digital_human/static/icons/play_video/music-4-blue.svg'
import Music4GrayIcon from '@/ai_modules/digital_human/static/icons/play_video/music-4-gray.svg'
import ClapperboardAmberIcon from '@/ai_modules/digital_human/static/icons/play_video/clapperboard-amber.svg'
import MessageCircleHeartGreenIcon from '@/ai_modules/digital_human/static/icons/play_video/message-circle-heart-green.svg'
import FlameAmberIcon from '@/ai_modules/digital_human/static/icons/play_video/flame-amber.svg'
import UserRoundCheckBlueIcon from '@/ai_modules/digital_human/static/icons/play_video/user-round-check-blue.svg'
import StoreGreenIcon from '@/ai_modules/digital_human/static/icons/play_video/store-green.svg'
import PopcornAmberIcon from '@/ai_modules/digital_human/static/icons/play_video/popcorn-amber.svg'
import ZapBlueIcon from '@/ai_modules/digital_human/static/icons/play_video/zap-blue.svg'
import TrendingUpGreenIcon from '@/ai_modules/digital_human/static/icons/play_video/trending-up-green.svg'
import LightbulbAmberIcon from '@/ai_modules/digital_human/static/icons/play_video/lightbulb-amber.svg'
import InboxGrayIcon from '@/ai_modules/digital_human/static/icons/play_video/inbox-gray.svg'
import PlayBlueIcon from '@/ai_modules/digital_human/static/icons/play_video/play-blue.svg'
import ShieldAlertAmberIcon from '@/ai_modules/digital_human/static/icons/play_video/shield-alert-amber.svg'
import Wand2WhiteIcon from '@/ai_modules/digital_human/static/icons/play_video/wand-2-white.svg'
import SparklesWhiteIcon from '@/ai_modules/digital_human/static/icons/play_video/sparkles-white.svg'

/** 爆款玩法：玩法标识（与站长 PlayVideoEnum::PLAYS 一致） */
export enum PlayKeyEnum {
    FACE = 'face',
    DANCE = 'dance',
    CLIP = 'clip'
}

/** 站长任务状态（PlayVideoEnum::STATUS_*） */
export enum PlayTaskStatusEnum {
    PENDING = 0,
    GENERATING = 1,
    SUCCESS = 2,
    FAILED = 3,
    TIMEOUT = 4
}

/** 跳舞换脸背景模式（与站长 bg_mode 一致） */
export enum BgModeEnum {
    KEEP = 'keep',
    UPLOAD = 'upload'
}

/** 拼接转场（与站长 transition 一致，是语义值不是云剪辑的 SubType，具体转场由中台映射） */
export enum TransitionEnum {
    NONE = 'none',
    FADE = 'fade',
    FLASH = 'flash'
}

/** 台词限制（与后端 PlayVideoEnum 一致：4.5 字/秒，上限 60 秒，最多 12 句） */
export const SPEECH_RUNES_PER_SECOND = 4.5
export const MAX_SPEECH_SECONDS = 60
export const MAX_LINES = 12
export const MAX_LINE_RUNES = 200
export const MAX_SPEECH_RUNES = 30
/** 口播字数下限（与站长 PlayVideoEnum::MIN_SPEECH_RUNES 一致）：对口型要求配音至少 1 秒，按 4.5 字/秒留余量 */
export const MIN_SPEECH_RUNES = 6
export const MAX_HOOK_RUNES = 20

/** 上传限制 */
export const FACE_IMAGE_MAX_MB = 10
export const FACE_IMAGE_ACCEPT = ['jpg', 'jpeg', 'png', 'webp']
export const CLIP_VIDEO_MAX_MB = 200
export const CLIP_VIDEO_ACCEPT = ['mp4', 'mov']
/** 用户视频时长上限（与站长 CLIP_PRODUCT_MAX_SEC / 中台一致）：成片=切片+整段用户视频，按秒计费 */
export const CLIP_VIDEO_MAX_SEC = 90
export const CLIP_VIDEO_DURATION: [number, number] = [1, CLIP_VIDEO_MAX_SEC]

/** 生成浮层：轮询节奏与进度策略（92% 封顶，真完成才拉满） */
export const POLL_FIRST_MS = 4000
export const POLL_INTERVAL_MS = 5000
export const POLL_BACKOFF_MS = 8000
export const PERCENT_START = 6
export const PERCENT_CAP = 92
export const EXPECT_SECONDS: Record<PlayKeyEnum, number> = {
    [PlayKeyEnum.FACE]: 200,
    [PlayKeyEnum.DANCE]: 150,
    [PlayKeyEnum.CLIP]: 180
}

/**
 * 页面直用的玩法图标。全部走静态 import：微信上传时「忽略未引用文件」会把只在运行时拼路径的
 * 图片剔除出包，真机上显示空白；静态 import 才会被编译器登记成可识别的路径引用。
 */
export const PLAY_ICONS = {
    flame: FlameAmberIcon,
    lightbulb: LightbulbAmberIcon,
    inbox: InboxGrayIcon,
    play: PlayBlueIcon,
    shieldAlert: ShieldAlertAmberIcon,
    wand: Wand2WhiteIcon,
    sparkles: SparklesWhiteIcon
}

export const PLAY_PAGE_PATH: Record<PlayKeyEnum, string> = {
    [PlayKeyEnum.FACE]: '/ai_modules/digital_human/pages/play_video_face/play_video_face',
    [PlayKeyEnum.DANCE]: '/ai_modules/digital_human/pages/play_video_dance/play_video_dance',
    [PlayKeyEnum.CLIP]: '/ai_modules/digital_human/pages/play_video_clip/play_video_clip'
}

export interface PlayItem {
    key: PlayKeyEnum
    name: string
    iconOn: string
    iconOff: string
    desc: string
    cap: string
    capBg: string
    capFg: string
    live: boolean
}

// 顺序与 PC 端 PLAYS 一致：默认选中的换脸改词排第一
export const PLAYS: PlayItem[] = [
    {
        key: PlayKeyEnum.FACE,
        name: '影视换脸改词',
        iconOn: VenetianMaskBlueIcon,
        iconOff: VenetianMaskGrayIcon,
        desc: '经典片段说你的词',
        cap: 'AI 换脸',
        capBg: '#F3EEFF',
        capFg: '#7B61FF',
        live: true
    },
    {
        key: PlayKeyEnum.DANCE,
        name: '跳舞换脸',
        iconOn: Music4BlueIcon,
        iconOff: Music4GrayIcon,
        desc: '热门舞蹈换你出镜',
        cap: 'AI 换脸',
        capBg: '#F3EEFF',
        capFg: '#7B61FF',
        live: true
    },
    {
        key: PlayKeyEnum.CLIP,
        name: '影视引流拼接',
        iconOn: FilmBlueIcon,
        iconOff: FilmGrayIcon,
        desc: '切片开场引爆完播',
        cap: '智能剪辑',
        capBg: '#EBF2FF',
        capFg: '#2680F7',
        live: true
    }
]

export interface PlayIntro {
    note: string
    steps: { icon: string; bg: string; t: string; d: string }[]
    how: string[]
    tip: string
}

/** 玩法介绍浮窗文案（对齐原型 INTRO） */
export const PLAY_INTRO: Record<PlayKeyEnum, PlayIntro> = {
    [PlayKeyEnum.FACE]: {
        note: '拉的是：反差感 & 记忆点',
        steps: [
            {
                icon: ClapperboardAmberIcon,
                bg: '#FFF3E0',
                t: '经典影视片段',
                d: '台词深入人心\n自带传播基因'
            },
            {
                icon: VenetianMaskBlueIcon,
                bg: '#E3EEFF',
                t: '换脸 + 改词',
                d: '脸换成你\n词改成你的产品'
            },
            {
                icon: MessageCircleHeartGreenIcon,
                bg: '#E8F8EF',
                t: '评论区裂变',
                d: '"这也行？"\n互动评论拉权重'
            }
        ],
        how: ['挑一个经典对白片段', '上传你的正脸照', '台本逐句改写成你的词', '选配音音色，生成'],
        tip: '为什么有效：观众对经典台词有肌肉记忆，你把词换成自家产品的瞬间会产生强烈反差，这种「意料之外」是评论区互动的主要来源，而互动权重直接影响推荐。'
    },
    [PlayKeyEnum.DANCE]: {
        note: '拉的是：模仿门槛低 & BGM 加成',
        steps: [
            {
                icon: FlameAmberIcon,
                bg: '#FFF3E0',
                t: '热门舞蹈模板',
                d: 'BGM 正在风口\n蹭热度自然流'
            },
            {
                icon: UserRoundCheckBlueIcon,
                bg: '#E3EEFF',
                t: '换脸出镜',
                d: '不用会跳\n脸换成你就行'
            },
            { icon: StoreGreenIcon, bg: '#E8F8EF', t: '人设引流', d: '片尾口播\n导关注/进店' }
        ],
        how: [
            '挑一个正在热的舞蹈模板',
            '上传你的正脸照',
            '可选换掉背景为你的门店/场景',
            '开片尾口播引导，生成'
        ],
        tip: '为什么有效：热门 BGM 本身带着流量池，平台会把使用同一音乐的内容推给同一批人。你不需要会跳，换脸就能加入这波流量，还能把背景换成自己的门店做场景植入。'
    },
    [PlayKeyEnum.CLIP]: {
        note: '拉的是：完播率 & 停留时长',
        steps: [
            {
                icon: PopcornAmberIcon,
                bg: '#FFF3E0',
                t: '吸睛切片开场',
                d: '名场面 10~20s\n观众停留看完'
            },
            {
                icon: ZapBlueIcon,
                bg: '#E3EEFF',
                t: '突然无缝拼接',
                d: '正上头时硬切\n进你的产品段'
            },
            {
                icon: TrendingUpGreenIcon,
                bg: '#E8F8EF',
                t: '流量承接',
                d: '完播/停留拉高\n推荐池给量'
            }
        ],
        how: ['挑一个吸睛切片模板', '上传一条你自己的视频', '设置切片保留时长与转场，生成'],
        tip: '为什么有效：平台把完播率和停留时长当成核心推荐指标。影视名场面能在前 3 秒留住人，等观众看完切片、正处在情绪高点时切进你的内容，这条视频的完播数据就已经赢了大半。'
    }
}

export const LEGAL_TEXT: Record<PlayKeyEnum, string> = {
    [PlayKeyEnum.FACE]:
        '换脸仅支持本人授权的人脸照片。请勿使用他人肖像，侵权责任由使用者自行承担。',
    [PlayKeyEnum.DANCE]: '换脸仅支持本人授权的人脸照片。舞蹈模板与 BGM 均来自平台授权曲库。',
    [PlayKeyEnum.CLIP]:
        '影视切片请使用已获授权的素材或平台版权合作片库；直接搬运影视剧片段可能被平台限流或下架，相关责任由使用者自行承担。'
}

export const WALL_TITLE: Record<PlayKeyEnum, string> = {
    [PlayKeyEnum.FACE]: '选一个影视片段',
    [PlayKeyEnum.DANCE]: '选一个舞蹈模板',
    [PlayKeyEnum.CLIP]: '选一个吸睛切片'
}

export const WALL_SUB: Record<PlayKeyEnum, string> = {
    [PlayKeyEnum.FACE]: '经典对白场面，进去后换脸并改写台词',
    [PlayKeyEnum.DANCE]: '自带爆款 BGM，进去后换脸出镜',
    [PlayKeyEnum.CLIP]: '作为视频开头，留住观众的那 10~20 秒'
}

export const CLIP_CAT_ALL = '全部'

/** 跳舞换脸背景模式（2026-09-03 用户定：去掉「平台场景」预设，只留原背景/自传背景） */
export const BG_MODES = [
    { key: BgModeEnum.KEEP, name: '保持原背景' },
    { key: BgModeEnum.UPLOAD, name: '我的背景' }
]

/** 配音音色（映射表在服务端） */
export const VOICES = ['相似音色', '清亮女声', '温柔女声', '磁性男声', '沉稳大叔']

export const KEEP_OPTIONS = [10, 15, 20]
export const KEEP_SEC_DEFAULT = 15
export const DEFAULT_HOOK_TEXT = '看到最后我人傻了…'

export const TRANSITIONS = [
    { key: TransitionEnum.NONE, name: '硬切', desc: '干脆利落' },
    { key: TransitionEnum.FADE, name: '叠化', desc: '柔和过渡' },
    { key: TransitionEnum.FLASH, name: '闪切', desc: '模糊冲刺' }
]
