<template>
    <view class="flex flex-col h-screen bg-[#F3F5FA]">
        <u-navbar
            :border-bottom="false"
            :is-fixed="false"
            :background="{ background: 'transparent' }"
            title="爆款玩法复刻"
            title-bold
        />

        <view class="grow min-h-0">
            <scroll-view class="h-full" scroll-y>
                <view class="px-[32rpx] pb-[220rpx]">
                    <view class="flex items-center gap-[12rpx] mt-[24rpx] mb-[20rpx]">
                        <play-section-title>选择玩法</play-section-title>
                        <view
                            class="px-[12rpx] py-[4rpx] rounded-[8rpx] bg-[#FEF2F2] flex items-center gap-[4rpx]"
                        >
                            <image :src="PLAY_ICONS.flame" class="w-[20rpx] h-[20rpx]" />
                            <text class="text-[19rpx] font-bold text-[#EF4444]">全网热门</text>
                        </view>
                        <view
                            class="ml-auto px-[18rpx] py-[8rpx] rounded-full bg-white shadow-sm active:opacity-70"
                            @click="showIntro = true"
                        >
                            <text class="text-[21rpx] font-semibold text-[#2680F7]">介绍玩法</text>
                        </view>
                    </view>
                    <view class="grid grid-cols-3 gap-[16rpx]">
                        <view
                            v-for="p in PLAYS"
                            :key="p.key"
                            class="relative bg-white rounded-[24rpx] p-[20rpx] pt-[26rpx] text-center transition-all duration-150"
                            :class="[
                                currPlay === p.key ? 'play-on' : '',
                                !p.live ? 'opacity-60' : 'active:scale-[0.98]'
                            ]"
                            @click="handleSwitchPlay(p)"
                        >
                            <view
                                class="absolute -top-[10rpx] right-[6rpx] px-[12rpx] py-[3rpx] rounded-full"
                                :style="{ background: p.live ? p.capBg : '#F4F6FA' }"
                            >
                                <text
                                    class="text-[17rpx] font-bold"
                                    :style="{ color: p.live ? p.capFg : '#8A93A3' }"
                                    >{{ p.live ? p.cap : '即将上线' }}</text
                                >
                            </view>
                            <view
                                class="w-[76rpx] h-[76rpx] rounded-[20rpx] mx-auto mb-[10rpx] flex items-center justify-center"
                                :class="currPlay === p.key ? 'bg-[#DBEAFE]' : 'bg-[#F7F8FA]'"
                            >
                                <image
                                    :src="currPlay === p.key ? p.iconOn : p.iconOff"
                                    class="w-[40rpx] h-[40rpx]"
                                />
                            </view>
                            <text
                                class="text-[22rpx] font-bold block"
                                :class="currPlay === p.key ? 'text-[#2680F7]' : 'text-[#1D2129]'"
                                >{{ p.name }}</text
                            >
                            <text class="text-[18rpx] text-[#9CA3AF] block mt-[4rpx]">{{
                                p.desc
                            }}</text>
                        </view>
                    </view>

                    <view class="mt-[36rpx] mb-[6rpx]">
                        <play-section-title>{{ WALL_TITLE[currPlay] }}</play-section-title>
                    </view>
                    <text class="text-[21rpx] text-[#9CA3AF] block mb-[18rpx]">{{
                        WALL_SUB[currPlay]
                    }}</text>

                    <view
                        v-if="currPlay === PlayKeyEnum.CLIP && clipCats.length > 1"
                        class="mb-[16rpx]"
                    >
                        <scroll-view scroll-x class="w-full">
                            <view class="whitespace-nowrap">
                                <view
                                    v-for="c in clipCats"
                                    :key="c"
                                    class="inline-block px-[22rpx] py-[10rpx] rounded-[14rpx] mr-[12rpx] border-[3rpx]"
                                    :class="
                                        clipCat === c
                                            ? 'bg-[#EBF2FF] border-[#2680F7]'
                                            : 'bg-white border-[transparent]'
                                    "
                                    @click="clipCat = c"
                                >
                                    <text
                                        class="text-[21rpx] font-semibold"
                                        :class="clipCat === c ? 'text-[#2680F7]' : 'text-[#8A93A3]'"
                                        >{{ c }}</text
                                    >
                                </view>
                            </view>
                        </scroll-view>
                    </view>

                    <view v-if="loading" class="wall-empty">
                        <u-loading mode="circle" size="48" />
                        <text class="text-[24rpx] text-[#9CA3AF] mt-[16rpx]">正在加载模板…</text>
                    </view>
                    <view v-else-if="loadError" class="wall-empty" @click="queryTemplateList">
                        <image :src="PLAY_ICONS.inbox" class="w-[56rpx] h-[56rpx]" />
                        <text class="text-[24rpx] font-semibold text-[#4B5563] mt-[12rpx]"
                            >模板加载失败</text
                        >
                        <view class="mt-[16rpx] px-[36rpx] py-[12rpx] rounded-full bg-[#EBF2FF]">
                            <text class="text-[22rpx] font-bold text-[#2680F7]">点击重试</text>
                        </view>
                    </view>
                    <view v-else-if="templates.length === 0" class="wall-empty">
                        <image :src="PLAY_ICONS.inbox" class="w-[56rpx] h-[56rpx]" />
                        <text class="text-[24rpx] font-semibold text-[#4B5563] mt-[12rpx]">{{
                            notice || '运营还没有上架这个玩法的模板'
                        }}</text>
                        <text class="text-[20rpx] text-[#C0C6D0] mt-[8rpx]"
                            >上架后这里会自动出现</text
                        >
                    </view>
                    <view v-else class="grid grid-cols-2 gap-[20rpx]">
                        <view
                            v-for="t in visibleTemplates"
                            :key="t.ref_id"
                            class="active:scale-[0.98] transition-all duration-150"
                            @click="handlePick(t)"
                        >
                            <view
                                class="relative rounded-[20rpx] overflow-hidden border-[3rpx] tpl-cover"
                                :class="
                                    picked?.ref_id === t.ref_id
                                        ? 'border-[#2680F7] shadow-lg'
                                        : 'border-[transparent]'
                                "
                            >
                                <image
                                    v-if="t.cover_url"
                                    :src="t.cover_url"
                                    class="absolute inset-0 w-full h-full"
                                    mode="aspectFill"
                                />
                                <view
                                    v-else
                                    class="absolute inset-0 flex flex-col items-center justify-center px-[16rpx]"
                                >
                                    <text
                                        class="text-white text-[26rpx] font-extrabold text-center leading-relaxed line-clamp-2"
                                        >{{ t.name }}</text
                                    >
                                    <text
                                        class="text-[rgba(255,255,255,0.7)] text-[18rpx] mt-[8rpx]"
                                        >{{ t.duration_sec || '--' }}s 示例片段</text
                                    >
                                </view>
                                <view
                                    v-if="t.heat"
                                    class="absolute top-[10rpx] left-[10rpx] px-[14rpx] py-[4rpx] rounded-full bg-[rgba(0,0,0,0.5)]"
                                >
                                    <text class="text-[18rpx] font-bold text-[#FFD666]">{{
                                        t.heat
                                    }}</text>
                                </view>
                                <view
                                    v-if="picked?.ref_id === t.ref_id"
                                    class="absolute top-[10rpx] right-[10rpx] w-[40rpx] h-[40rpx] rounded-full bg-[#2680F7] flex items-center justify-center"
                                >
                                    <u-icon name="checkmark" color="#fff" size="22" />
                                </view>
                                <view
                                    v-if="t.video_url"
                                    class="absolute right-[10rpx] bottom-[10rpx] w-[48rpx] h-[48rpx] rounded-full bg-[rgba(255,255,255,0.88)] flex items-center justify-center"
                                    @click.stop="handlePreview(t)"
                                >
                                    <image
                                        :src="PLAY_ICONS.play"
                                        class="w-[26rpx] h-[26rpx] ml-[2rpx]"
                                    />
                                </view>
                            </view>
                            <text
                                class="text-[24rpx] font-semibold text-[#1D2129] block text-center mt-[10rpx] line-clamp-1"
                                >{{ t.name }}</text
                            >
                            <text class="text-[19rpx] text-[#9AA3B2] block text-center mt-[2rpx]">{{
                                tplSub(t)
                            }}</text>
                        </view>
                    </view>

                    <view
                        class="bg-[#FFFBEB] rounded-[24rpx] p-[24rpx] mt-[28rpx] flex gap-[14rpx]"
                    >
                        <image
                            :src="PLAY_ICONS.shieldAlert"
                            class="w-[30rpx] h-[30rpx] flex-shrink-0"
                        />
                        <text class="text-[20rpx] text-[#B45309] leading-relaxed flex-1">{{
                            LEGAL_TEXT[currPlay]
                        }}</text>
                    </view>
                </view>
            </scroll-view>
        </view>

        <view
            class="fixed left-0 right-0 bottom-0 bg-white px-[32rpx] pt-[20rpx] pb-[calc(24rpx+env(safe-area-inset-bottom))] bar-shadow z-50"
        >
            <view class="flex items-center gap-[24rpx]">
                <view class="flex-shrink-0 max-w-[200rpx]">
                    <text class="text-[19rpx] text-[#9CA3AF] block">已选</text>
                    <text class="text-[24rpx] font-bold text-[#1D2129] line-clamp-1 block">{{
                        picked?.name || '—'
                    }}</text>
                </view>
                <view
                    class="flex-1 h-[92rpx] rounded-full flex items-center justify-center btn-blue"
                    :class="picked ? 'active:opacity-80' : 'opacity-50'"
                    @click="handleGoMake"
                >
                    <image
                        v-if="picked"
                        :src="PLAY_ICONS.wand"
                        class="w-[30rpx] h-[30rpx] mr-[8rpx]"
                    />
                    <text class="text-[28rpx] font-bold text-white">{{
                        picked ? '制作同款' : '暂无模板'
                    }}</text>
                </view>
            </view>
        </view>

        <video-preview v-model="showVideoPreview" title="模板预览" :video-url="previewUrl" />
        <play-intro-popup v-model="showIntro" :play="currPlay" />
    </view>
</template>

<script setup lang="ts">
import { onLoad } from '@dcloudio/uni-app'
import { getPlayVideoTemplates } from '@/api/play_video'
import VideoPreview from '@/components/video-preview/video-preview.vue'
import PlaySectionTitle from '../../components/play-video/play-section-title.vue'
import PlayIntroPopup from '../../components/play-video/popups/play-intro-popup.vue'
import {
    CLIP_CAT_ALL,
    LEGAL_TEXT,
    PLAYS,
    PLAY_ICONS,
    PLAY_PAGE_PATH,
    PlayKeyEnum,
    WALL_SUB,
    WALL_TITLE,
    type PlayItem
} from '../../enums/play_video'
import { setCurrentTemplate, setPlayPricing, type PlayTemplate } from '../../utils/play_video'

const currPlay = ref<PlayKeyEnum>(PlayKeyEnum.FACE)
const showIntro = ref(false)
const loading = ref(false)
const loadError = ref(false)
const notice = ref('')
const templates = ref<PlayTemplate[]>([])
// 三个玩法的选中态各自记忆（原型行为）
const pickedMap = reactive<Record<PlayKeyEnum, PlayTemplate | null>>({
    [PlayKeyEnum.FACE]: null,
    [PlayKeyEnum.DANCE]: null,
    [PlayKeyEnum.CLIP]: null
})
const picked = computed(() => pickedMap[currPlay.value])

// clip 分类（按实际模板去重；切分类后已选不在列表则回落第一条）
const clipCat = ref(CLIP_CAT_ALL)
const clipCats = computed(() => [
    CLIP_CAT_ALL,
    ...new Set(templates.value.map((t) => t.category).filter(Boolean) as string[])
])
const visibleTemplates = computed(() =>
    currPlay.value === PlayKeyEnum.CLIP && clipCat.value !== CLIP_CAT_ALL
        ? templates.value.filter((t) => t.category === clipCat.value)
        : templates.value
)
watch([clipCat, visibleTemplates], () => {
    const list = visibleTemplates.value
    const curr = pickedMap[currPlay.value]
    if (list.length && (!curr || !list.some((t) => t.ref_id === curr.ref_id))) {
        pickedMap[currPlay.value] = list[0]
    }
})

const handleSwitchPlay = (p: PlayItem) => {
    if (!p.live) {
        uni.$u.toast(`${p.name}即将上线，敬请期待`)
        return
    }
    if (currPlay.value === p.key) return
    currPlay.value = p.key
    clipCat.value = CLIP_CAT_ALL
    queryTemplateList()
}

const queryTemplateList = async () => {
    const play = currPlay.value
    loading.value = true
    loadError.value = false
    try {
        const data: any = await getPlayVideoTemplates({ play })
        if (play !== currPlay.value) return // 切玩法后旧请求晚返回，丢弃
        templates.value = (data.templates || []) as PlayTemplate[]
        notice.value = data.notice || ''
        setPlayPricing(data.prices, data.balance)
        const curr = pickedMap[play]
        if (!curr || !templates.value.some((t) => t.ref_id === curr.ref_id)) {
            pickedMap[play] = templates.value[0] || null
        }
    } catch (e) {
        if (play !== currPlay.value) return
        loadError.value = true
        templates.value = []
    } finally {
        if (play === currPlay.value) loading.value = false
    }
}

const tplSub = (t: PlayTemplate) => {
    const head =
        currPlay.value === PlayKeyEnum.FACE
            ? (t.face_count || 1) >= 2
                ? '双人'
                : '单人'
            : t.category || '热门'
    return t.duration_sec ? `${head} · ${t.duration_sec}s` : head
}

const showVideoPreview = ref(false)
const previewUrl = ref('')
const handlePreview = (t: PlayTemplate) => {
    if (!t.video_url) return
    previewUrl.value = t.video_url
    showVideoPreview.value = true
}

const handlePick = (t: PlayTemplate) => {
    pickedMap[currPlay.value] = t
}

const handleGoMake = () => {
    const tpl = picked.value
    if (!tpl) {
        uni.$u.toast(notice.value || '运营还没有上架这个玩法的模板')
        return
    }
    setCurrentTemplate(tpl)
    uni.navigateTo({ url: PLAY_PAGE_PATH[currPlay.value] })
}

onLoad((query: any) => {
    const play = query?.play as PlayKeyEnum | undefined
    if (play && Object.values(PlayKeyEnum).includes(play)) currPlay.value = play
    queryTemplateList()
})
</script>

<style lang="scss" scoped>
.play-on {
    border: 3rpx solid #2680f7;
    background: #f5f9ff !important;
    box-shadow: 0 8rpx 28rpx rgba(38, 128, 247, 0.15);
}
.wall-empty {
    @apply bg-white rounded-[24rpx] py-[64rpx] flex flex-col items-center;
}
.tpl-cover {
    aspect-ratio: 9 / 16;
    background: linear-gradient(160deg, #2b3a5c, #151c2e);
}
.bar-shadow {
    box-shadow: 0 -4rpx 20rpx rgba(0, 0, 0, 0.06);
}
.btn-blue {
    background: linear-gradient(90deg, #2680f7, #3e9bff);
}
</style>
