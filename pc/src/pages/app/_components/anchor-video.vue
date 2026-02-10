<template>
    <div class="video-item group">
        <div
            class="grow min-h-0 relative h-full transition-transform duration-500 group-hover:scale-105"
            :style="{
                background: `linear-gradient(180deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0) 50%, rgba(0,0,0,0.9) 100%), url(${item.pic}) no-repeat center`,
                backgroundSize: 'cover',
            }">
            <div class="w-full px-4 pt-4 break-all relative z-[8888]">
                <div class="text-white font-black text-[14px] line-clamp-1 drop-shadow-md">
                    {{ item.name }}
                </div>
            </div>

            <div
                class="absolute right-3 top-3 z-[10000] w-9 h-9 flex items-center justify-center bg-[#ffffff]/10 rounded-full invisible group-hover:visible backdrop-blur-md border border-[#ffffff]/10 transition-all"
                :class="[activeVideo == item.id ? '!visible !bg-primary !border-primary shadow-[#0065fb]/30' : '']">
                <ElPopover
                    popper-class="!w-[212px] !min-w-[212px] !p-2 !rounded-2xl !border-slate-800 !bg-[#0f172a]/95 !backdrop-blur-xl"
                    :show-arrow="false"
                    :popper-options="{
                        modifiers: [{ name: 'offset', options: { offset: [100, 20] } }],
                    }"
                    @show="visibleChange(true, item.id)"
                    @hide="visibleChange(false, item.id)">
                    <template #reference>
                        <div class="rotate-90 cursor-pointer w-full h-full flex items-center justify-center">
                            <Icon name="el-icon-MoreFilled" color="#ffffff" :size="16"></Icon>
                        </div>
                    </template>

                    <div class="flex flex-col gap-1">
                        <DefineTemplate v-slot="{ label, icon }">
                            <div
                                class="h-10 px-3 rounded-xl cursor-pointer flex items-center gap-3 hover:bg-[#ffffff]/10 transition-all group">
                                <span
                                    class="flex w-6 h-6 rounded-lg bg-[#ffffff]/5 items-center justify-center group-hover:bg-[#0065fb]/20 transition-colors">
                                    <Icon :name="icon" color="#ffffff" :size="14"></Icon>
                                </span>
                                <span class="text-[13px] font-medium text-[#ffffff]/80 group-hover:text-white">{{
                                    label
                                }}</span>
                            </div>
                        </DefineTemplate>

                        <div @click="handleDownLoad(item.url)">
                            <UseTemplate label="下载形象" icon="local-icon-download" />
                        </div>
                        <div @click="handlePlay(item.url)">
                            <UseTemplate label="播放形象" icon="local-icon-play" />
                        </div>
                        <div @click="handleDelete(item.id)">
                            <UseTemplate label="删除形象" icon="local-icon-delete" />
                        </div>
                    </div>
                </ElPopover>
            </div>

            <template v-if="getStatus(item) == 1">
                <div
                    class="absolute inset-0 flex items-center justify-center z-[99] cursor-pointer group-hover:bg-black/10 transition-colors"
                    @click="handlePlay(item.url)">
                    <div
                        class="w-12 h-12 rounded-full bg-[#ffffff]/10 backdrop-blur-xl border border-[#ffffff]/20 flex items-center justify-center transition-all group-hover:bg-primary group-hover:scale-110 group-hover:border-primary group-hover:shadow-light group-hover:shadow-[#0065fb]/40">
                        <play-btn :icon-size="38"></play-btn>
                    </div>
                </div>
            </template>

            <template v-else>
                <div
                    class="absolute inset-0 z-[88] px-6 bg-[#020617]/80 backdrop-blur-[2px] flex flex-col items-center justify-center">
                    <div class="flex justify-center items-center flex-col gap-3">
                        <template v-if="getStatus(item) == 2">
                            <div
                                class="w-12 h-12 flex items-center justify-center rounded-2xl bg-[#ef4444]/20 border border-[#ef4444]/20 mb-1">
                                <Icon name="local-icon-video2" color="#f87171" :size="24"></Icon>
                            </div>
                            <span class="text-white font-black text-sm text-center">{{
                                item.remark || "生成失败"
                            }}</span>
                            <span class="text-[#ffffff]/40 text-[11px] text-center"> （请检查训练的视频文件） </span>
                        </template>
                        <template v-else>
                            <div
                                class="w-10 h-10 rounded-full border-[3px] border-[#0065fb]/20 border-t-primary animate-spin mb-2"></div>
                            <span class="text-white font-black text-sm uppercase tracking-widest">生成中...</span>
                            <span class="text-[#0065fb]/60 font-medium text-[11px] text-center animate-pulse"
                                >几分钟即可生成形象</span
                            >
                        </template>
                    </div>
                </div>
            </template>

            <div class="absolute bottom-5 left-0 w-full z-[51] text-center">
                <div class="text-[11px] font-medium text-[#ffffff]/30 italic tracking-wider uppercase">
                    {{ item.create_time }}
                </div>
            </div>
        </div>
    </div>
    <preview-video v-if="showVideo" ref="videoPlayerRef" @close="showVideo = false"></preview-video>
</template>

<script setup lang="ts">
import { createReusableTemplate } from "@vueuse/core";

// Props, Emits, 业务逻辑完全保持不变
const props = withDefaults(
    defineProps<{
        item: Record<string, any>;
    }>(),
    {
        item: () => ({
            id: 0,
            name: "",
            pic: "",
            status: 0,
            url: "",
            remark: "",
            source_type: "",
        }),
    }
);

const emit = defineEmits(["delete", "retry"]);

const activeVideo = ref<number | undefined>();

const getStatus = (data: Record<string, any>): number => {
    const { status, source_type } = data;
    const anchorStatusMapping: Record<string, any> = {
        human_anchor: { 1: 1, 2: 2, default: 0 },
        shanjian_anchor: { 1: 1, 2: 2, 5: 2, 3: 3, default: 0 },
        public_anchor: { 1: 0, 2: 1, 3: 2, default: 0 },
    };
    return anchorStatusMapping[source_type][status] || anchorStatusMapping[source_type]["default"];
};

const visibleChange = (flag: boolean, id: number) => {
    if (!flag) {
        activeVideo.value = undefined;
    } else {
        activeVideo.value = id;
    }
};

const handleRetry = (id: number) => {
    emit("retry", id);
};

const handleDownLoad = (url: string) => {
    feedback.loading("保存中");
    downloadFile(url)
        .then(() => {
            feedback.closeLoading();
            feedback.msgSuccess("下载成功");
        })
        .catch(() => {
            feedback.closeLoading();
            feedback.msgError("下载失败");
        });
};

const handleDelete = async (id: number) => {
    emit("delete", id);
};

const videoPlayerRef = shallowRef();
const showVideo = ref(false);
const handlePlay = async (url: string) => {
    showVideo.value = true;
    await nextTick();
    videoPlayerRef.value.open();
    videoPlayerRef.value.setUrl(url);
};

const [DefineTemplate, UseTemplate] = createReusableTemplate<{
    label: string;
    icon: string;
}>();
</script>

<style lang="scss" scoped>
.video-item {
    @apply h-[295px] relative overflow-hidden bg-slate-950 rounded-[20px];
    -webkit-mask-image: -webkit-radial-gradient(white, black);
    mask-image: radial-gradient(white, black);
    isolation: isolate;
    backface-visibility: hidden;
}
</style>
