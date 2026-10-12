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
                        <view
                            class="flex items-center gap-[20rpx] px-[20rpx] h-[100rpx] rounded-[20rpx] bg-[#F7F9FC]"
                            :class="!selectedId ? 'border-[2rpx] border-solid border-primary' : 'border-[2rpx] border-solid border-[transparent]'"
                            @click="pickRandom">
                            <text class="flex-1 text-[28rpx] font-bold text-[#0D1117]">系统随机</text>
                            <u-icon v-if="!selectedId" name="checkmark" color="#0065fb" size="28" />
                        </view>
                        <view
                            v-for="item in musics"
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
                        <text v-if="!loading && musics.length === 0" class="text-center text-[24rpx] text-[#9CA3AF] py-[40rpx]">
                            暂无上架曲目，将使用系统随机
                        </text>
                    </view>
                </scroll-view>
            </view>
        </view>
    </u-popup>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { getSystemMusicList } from "@/api/digital_human";
import { useAudio } from "@/hooks/useAudio";

const props = defineProps<{
    modelValue: boolean;
    selectedId?: string;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: boolean): void;
    (e: "confirm", value: { id: string; name: string; audio_url: string }): void;
}>();

const show = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit("update:modelValue", value),
});

const musics = ref<any[]>([]);
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
        const res = await getSystemMusicList();
        musics.value = Array.isArray(res) ? res : [];
    } catch {
        musics.value = [];
    } finally {
        loading.value = false;
    }
};

watch(
    () => props.modelValue,
    (open) => {
        if (open) load();
        if (!open) {
            stop();
            playingId.value = "";
        }
    },
);

const close = () => emit("update:modelValue", false);

const pickRandom = () => {
    emit("confirm", { id: "", name: "", audio_url: "" });
    close();
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
