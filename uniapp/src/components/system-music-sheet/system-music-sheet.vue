<template>
    <u-popup v-model="show" mode="bottom" border-radius="28" :safe-area-inset-bottom="true">
        <view class="flex flex-col bg-white" style="height: 70vh">
            <view class="flex items-center justify-between px-[32rpx] pt-[28rpx] pb-[12rpx]">
                <text class="text-[32rpx] font-extrabold text-[#0D1117]">系统音乐</text>
                <view class="w-[56rpx] h-[56rpx] flex items-center justify-center" @click="close">
                    <u-icon name="close" color="#9CA3AF" size="28" />
                </view>
            </view>
            <view class="grow min-h-0">
                <scroll-view scroll-y class="h-full">
                    <view class="px-[32rpx] pb-[32rpx] flex flex-col gap-[12rpx]">
                        <!-- 分组筛选：全部 + 后台维护的各分组 -->
                        <music-group-tabs v-model="activeGroup" :groups="groups" />
                        <!--
                            随机卡片：跟在分组 tab 下方，随机范围就是当前 tab。
                            全部 tab → 全库随机；某个分组 tab → 组内随机（allowGroupRandom 时）。
                            和下面的「曲目行」区分开：这个是不指定单曲、每次随机一首。
                        -->
                        <view
                            v-if="activeGroup === 0 || allowGroupRandom"
                            class="flex items-center gap-[20rpx] px-[20rpx] h-[100rpx] rounded-[20rpx] bg-[#F7F9FC]"
                            :class="
                                !selectedId && pickedGroupId === activeGroup
                                    ? 'border-[2rpx] border-solid border-primary'
                                    : 'border-[2rpx] border-solid border-[transparent]'
                            "
                            @click="pickRandomInTab">
                            <view class="flex-1 min-w-0">
                                <text class="block text-[28rpx] font-bold text-[#0D1117]">
                                    {{ activeGroup ? "本组随机" : "全库随机" }}
                                </text>
                                <text class="block text-[22rpx] text-[#9CA3AF] mt-[4rpx]">{{ randomCardHint }}</text>
                            </view>
                            <u-icon
                                v-if="!selectedId && pickedGroupId === activeGroup"
                                name="checkmark"
                                color="#0065fb"
                                size="28" />
                        </view>
                        <view
                            v-for="item in visibleMusics"
                            :key="item.id"
                            class="flex items-center gap-[20rpx] px-[20rpx] h-[100rpx] rounded-[20rpx] bg-white border-[2rpx] border-solid"
                            :class="selectedId === item.id ? 'border-primary' : 'border-[#F0F2F5]'"
                            @click="pick(item)">
                            <view
                                class="w-[64rpx] h-[64rpx] rounded-[16rpx] flex items-center justify-center flex-shrink-0"
                                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                                @click.stop="toggle(item)">
                                <u-icon
                                    :name="playingId === item.id && isPlaying ? 'pause' : 'play-right'"
                                    color="#ffffff"
                                    size="28" />
                            </view>
                            <view class="flex-1 min-w-0">
                                <text class="block text-[28rpx] font-bold text-[#0D1117] line-clamp-1">{{ item.name }}</text>
                                <text class="block text-[22rpx] text-[#9CA3AF] mt-[4rpx]">{{ formatDuration(item.duration_sec) }}</text>
                            </view>
                            <u-icon v-if="selectedId === item.id" name="checkmark" color="#0065fb" size="28" />
                        </view>
                        <text v-if="!loading && !visibleMusics.length" class="text-center text-[24rpx] text-[#9CA3AF] py-[40rpx]">
                            {{ musics.length ? "该分组暂无曲目" : "暂无上架曲目，将回落到全库随机" }}
                        </text>
                    </view>
                </scroll-view>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { getSystemMusicGroups, getSystemMusicList } from "@/api/digital_human";
import { useAudio } from "@/hooks/useAudio";
import MusicGroupTabs from "@/components/music-group-tabs/music-group-tabs.vue";

const props = defineProps<{
    modelValue: boolean;
    selectedId?: string;
    /** 打开时预选的分组：0 = 全部 */
    groupId?: number;
    /** 是否允许「本组随机」（不指定单曲，每次从该分组随机一首） */
    allowGroupRandom?: boolean;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "confirm", value: { id: string; name: string; audio_url: string; group_id: number }): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

const musics = ref<any[]>([]);
// 分组 tab：0 = 全部（不筛选）
const groups = ref<Array<{ id: number; name: string }>>([]);
const activeGroup = ref(0);
// 已选中的「分组随机」分组（selectedId 为空时才显示勾选）
const pickedGroupId = ref(0);
const groupMusicCount = computed(() => {
    if (!activeGroup.value) return 0;
    return musics.value.filter((item: any) => Number(item?.group_id) === activeGroup.value).length;
});
/** 随机卡片的副标题：随机范围跟着当前分组 tab 走，带上该范围的曲目总数 */
const randomCardHint = computed(() => {
    if (!activeGroup.value) return `全部曲目共 ${musics.value.length} 首，每次随机一首`;
    if (groupMusicCount.value <= 0) return "该分组暂无曲目，保存后将回落到全库随机";
    return `该分组共 ${groupMusicCount.value} 首，每次从中随机一首`;
});
const visibleMusics = computed(() => {
    if (!activeGroup.value) return musics.value;
    return musics.value.filter((item: any) => Number(item?.group_id) === activeGroup.value);
});
const loading = ref(false);
const playingId = ref("");
const { isPlaying, pause, play, setUrl, stop } = useAudio();

const formatDuration = (sec: number) => {
    const total = Math.max(0, Number(sec) || 0);
    const minute = Math.floor(total / 60);
    const second = total % 60;
    return `${minute}:${String(second).padStart(2, "0")}`;
};

const load = async () => {
    loading.value = true;
    try {
        // 曲库与分组一起拉：分组拉不到时按「全部」展示，不影响选曲
        const [list, groupList] = await Promise.all([
            getSystemMusicList().catch(() => []),
            getSystemMusicGroups().catch(() => []),
        ]);
        musics.value = Array.isArray(list) ? list : [];
        groups.value = Array.isArray(groupList) ? groupList : [];
    } catch {
        musics.value = [];
        groups.value = [];
    } finally {
        loading.value = false;
    }
};

watch(
    () => props.modelValue,
    (open) => {
        if (open) {
            pickedGroupId.value = Number(props.groupId || 0);
            activeGroup.value = Number(props.groupId || 0);
            load();
        }
        if (!open) {
            stop();
            playingId.value = "";
        }
    },
);

const close = () => emit("update:modelValue", false);

const pickRandom = () => {
    pickedGroupId.value = 0;
    emit("confirm", { id: "", name: "", audio_url: "", group_id: 0 });
    close();
};

const pickGroupRandom = () => {
    if (!activeGroup.value) {
        pickRandom();
        return;
    }
    pickedGroupId.value = activeGroup.value;
    emit("confirm", { id: "", name: "", audio_url: "", group_id: Number(activeGroup.value) });
    close();
};

/** 随机卡片：全部 tab 走全库随机，分组 tab 走组内随机 */
const pickRandomInTab = () => {
    if (activeGroup.value > 0 && props.allowGroupRandom) {
        pickGroupRandom();
        return;
    }
    pickRandom();
};

const pick = (item: any) => {
    if (props.selectedId === item.id) {
        pickRandom();
        return;
    }
    emit("confirm", {
        id: String(item.id),
        name: String(item.name || ""),
        audio_url: String(item.audio_url || ""),
        group_id: Number(item?.group_id || 0),
    });
    close();
};

const toggle = (item: any) => {
    if (!item.audio_url) return;
    if (playingId.value === item.id && isPlaying.value) {
        pause();
        return;
    }
    playingId.value = item.id;
    setUrl(item.audio_url);
    play();
};
</script>
