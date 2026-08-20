<template>
    <popup
        ref="popupRef"
        width="480px"
        top="5vh"
        cancel-button-text=""
        confirm-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        style="padding: 0"
        :show-close="false"
        @close="close">
        <div class="bg-white rounded-2xl overflow-hidden flex flex-col">
            <div class="px-6 py-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-[#0065fb]/10 text-primary flex items-center justify-center">
                        <Icon name="el-icon-Lightning" :size="18" />
                    </div>
                    <span class="text-gray-950 text-lg font-[1000] tracking-tight">算力消耗说明</span>
                </div>
                <div class="w-9 h-9 cursor-pointer" @click="close">
                    <close-btn />
                </div>
            </div>

            <div class="px-6 py-5 flex flex-col gap-4">
                <div
                    class="bg-white rounded-2xl overflow-hidden border border-slate-100 shadow-[0_2px_12px_rgba(0,0,0,0.06)]">
                    <div class="flex items-center gap-4 px-5 py-4 border-b border-slate-100">
                        <div class="flex-1 min-w-0">
                            <p class="text-[15px] font-extrabold text-gray-900 leading-tight">{{ getScene.title }}</p>
                            <p class="text-xs text-slate-400 mt-1">{{ getScene.desc }}</p>
                        </div>
                    </div>

                    <div class="px-5">
                        <div v-if="type === 1" class="flex items-center justify-between h-14 border-b border-slate-100">
                            <div class="flex items-center gap-2.5">
                                <div class="w-2 h-2 rounded-full bg-[#0EA5E9] flex-shrink-0" />
                                <span class="text-sm text-slate-600">声音克隆费用</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-base font-extrabold text-primary">
                                    {{ getTokenByScene(TokensSceneEnum.HUMAN_VOICE_SHANJIAN).score }}
                                </span>
                                <span class="text-xs text-slate-400">
                                    {{ getTokenByScene(TokensSceneEnum.HUMAN_VOICE_SHANJIAN).unit }}
                                </span>
                            </div>
                        </div>

                        <div v-if="type === 1" class="flex items-center justify-between h-14 border-b border-slate-100">
                            <div class="flex items-center gap-2.5">
                                <div class="w-2 h-2 rounded-full bg-[#8B5CF6] flex-shrink-0" />
                                <span class="text-sm text-slate-600">形象克隆费用</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-base font-extrabold text-primary">
                                    {{ getTokenByScene(TokensSceneEnum.HUMAN_AVATAR_SHANJIAN).score }}
                                </span>
                                <span class="text-xs text-slate-400">
                                    {{ getTokenByScene(TokensSceneEnum.HUMAN_AVATAR_SHANJIAN).unit }}
                                </span>
                            </div>
                        </div>

                        <div v-if="!Array.isArray(getScene.key)" class="flex items-center justify-between h-14">
                            <div class="flex items-center gap-2.5">
                                <div class="w-2 h-2 rounded-full bg-primary flex-shrink-0" />
                                <span class="text-sm text-slate-600">视频合成费用</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-base font-extrabold text-primary">
                                    {{ getTokenByScene(getScene.key).score }}
                                </span>
                                <span class="text-xs text-slate-400">
                                    {{ getTokenByScene(getScene.key).unit }}
                                </span>
                            </div>
                        </div>

                        <div v-if="Array.isArray(getScene.key)">
                            <div
                                v-for="(item, index) in getScene.key"
                                :key="index"
                                class="flex items-center justify-between h-14"
                                :class="index < getScene.key.length - 1 ? 'border-b border-slate-100' : ''">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-2 h-2 rounded-full bg-primary flex-shrink-0" />
                                    <span class="text-sm text-slate-600">{{ item.name }}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-base font-extrabold text-primary">
                                        {{ getTokenByScene(item.key).score }}
                                    </span>
                                    <span class="text-xs text-slate-400">
                                        {{ getTokenByScene(item.key).unit }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-start gap-3 bg-[#EBF2FF] rounded-xl px-4 py-3">
                    <Icon name="el-icon-InfoFilled" color="#0065fb" :size="16" class="flex-shrink-0 mt-0.5" />
                    <span class="text-xs text-slate-500 leading-relaxed">
                        以上为单次任务消耗算力，实际扣费以任务完成时为准。算力不足时请前往充值中心补充。
                    </span>
                </div>
            </div>

            <div
                class="px-6 py-4 border-t border-slate-100 flex items-center justify-end shrink-0 bg-white shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
                <button
                    class="px-8 h-10 rounded-xl bg-primary text-white text-sm font-[1000] shadow-lg shadow-[#0065fb]/20 hover:bg-[#0056d6] hover:scale-[1.02] active:scale-95 transition-all"
                    @click="close">
                    我知道了
                </button>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { MontageTypeEnum } from "@/pages/app/digital_human/_enums";

const props = withDefaults(
    defineProps<{
        modelValue?: boolean;
        type: MontageTypeEnum;
    }>(),
    {
        type: MontageTypeEnum.REAL_PERSON_MIX,
        modelValue: false,
    },
);

const emit = defineEmits(["update:modelValue", "close"]);

const popupRef = shallowRef();

const getScene = computed(() => {
    const descriptions: Record<number, { title: string; desc: string; key: string | { name: string; key: string }[] }> =
        {
            [MontageTypeEnum.REAL_PERSON_MIX]: {
                title: "数字人口播混剪",
                desc: "数字人+文案+素材智能混剪",
                key: TokensSceneEnum.HUMAN_VIDEO_SHANJIAN,
            },
            [MontageTypeEnum.REAL_PERSON_AI]: {
                title: "真人口播智剪",
                desc: "上传真人口播视频，输出网感口播视频",
                key: TokensSceneEnum.SHANJIAN_CLIP_VIDEO,
            },
            [MontageTypeEnum.MATERIAL_MIX]: {
                title: "素材混剪",
                desc: "上传素材文案，自动生成视频",
                key: TokensSceneEnum.SHANJIAN_MATERIAL_VIDEO,
            },
            [MontageTypeEnum.NEWS_BODY]: {
                title: "新闻体视频",
                desc: "自动生成新闻体混剪视频",
                key: TokensSceneEnum.SHANJIAN_NEWS_VIDEO,
            },
            [MontageTypeEnum.ONE_SENTENCE_VIDEO]: {
                title: "一句话生成视频",
                desc: "一段话或根据场景生成视频",
                key: [
                    { name: "480P 图片转视频版", key: TokensSceneEnum.SEEDANCE2_480P_IMAGE2VIDEO },
                    { name: "480P 视频转视频版", key: TokensSceneEnum.SEEDANCE2_480P_VIDEO2VIDEO },
                    { name: "720P 图片转视频版", key: TokensSceneEnum.SEEDANCE2_720P_IMAGE2VIDEO },
                    { name: "720P 视频转视频版", key: TokensSceneEnum.SEEDANCE2_720P_VIDEO2VIDEO },
                ],
            },
            [MontageTypeEnum.STORYBOARD_MIX]: {
                title: "分镜混剪",
                desc: "分镜混剪",
                key: TokensSceneEnum.STORYBOARD_MIX,
            },
        };
    return descriptions[props.type];
});

const userStore = useUserStore();
const getTokenByScene = (key: string) => userStore.getTokenByScene(key);

const open = () => popupRef.value?.open();

const close = () => {
    emit("update:modelValue", false);
    emit("close");
};

watch(
    () => props.modelValue,
    (val) => {
        if (val) open();
    },
);

defineExpose({ open, close });
</script>
