<template>
    <popup
        ref="popupRef"
        cancel-button-text=""
        confirm-button-text=""
        header-class="!p-0"
        footer-class="!p-0"
        width="500px"
        style="padding: 0"
        :show-close="false"
        @close="close">
        <div class="bg-white rounded-2xl overflow-hidden flex flex-col">
            <div class="px-6 py-4 flex items-center justify-between border-b border-slate-100 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-[#0065fb]/10 flex items-center justify-center">
                        <Icon name="el-icon-InfoFilled" color="#0065fb" :size="16" />
                    </div>
                    <span class="text-gray-950 text-lg font-[1000] tracking-tight">上传素材要求</span>
                </div>
                <div class="w-9 h-9 cursor-pointer" @click="close">
                    <close-btn />
                </div>
            </div>

            <div class="px-6 py-5 flex flex-col gap-4 max-h-[60vh] overflow-y-auto">
                <div class="rounded-2xl overflow-hidden border border-slate-100 shadow-[0_2px_12px_rgba(0,0,0,0.04)]">
                    <div class="flex items-center gap-2.5 px-4 py-3 bg-[#0065fb]/5 border-b border-[#0065fb]/10">
                        <div class="w-6 h-6 rounded-lg bg-[#0065fb]/10 flex items-center justify-center flex-shrink-0">
                            <Icon name="el-icon-VideoPlay" color="#0065fb" :size="13" />
                        </div>
                        <span class="text-[13px] font-[1000] text-primary tracking-wide">视频要求</span>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <div
                            v-for="(rule, i) in VIDEO_RULES"
                            :key="i"
                            class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50/60 transition-colors">
                            <div class="w-1.5 h-1.5 rounded-full bg-[#0065fb]/40 mt-1.5 flex-shrink-0" />
                            <div class="text-[12px] text-slate-500 leading-relaxed flex-1">
                                <span class="font-black text-slate-700">{{ rule.label }}：</span>{{ rule.value }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl overflow-hidden border border-slate-100 shadow-[0_2px_12px_rgba(0,0,0,0.04)]">
                    <div class="flex items-center gap-2.5 px-4 py-3 bg-emerald-50 border-b border-emerald-100">
                        <div class="w-6 h-6 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                            <Icon name="el-icon-Picture" color="#10b981" :size="13" />
                        </div>
                        <span class="text-[13px] font-[1000] text-emerald-600 tracking-wide">图片要求</span>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <div
                            v-for="(rule, i) in IMAGE_RULES"
                            :key="i"
                            class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50/60 transition-colors">
                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-400/50 mt-1.5 flex-shrink-0" />
                            <div class="flex-1">
                                <div class="text-[12px] text-slate-500 leading-relaxed">
                                    <span class="font-black text-slate-700">{{ rule.label }}：</span>{{ rule.value }}
                                </div>
                                <div v-if="rule.sub" class="mt-1.5 flex flex-col gap-1">
                                    <div
                                        v-for="(s, si) in rule.sub"
                                        :key="si"
                                        class="flex items-start gap-1.5 text-[11px] text-slate-400 leading-relaxed">
                                        <span class="text-slate-300 mt-px">—</span>
                                        <span>
                                            {{ s.text
                                            }}<span v-if="s.highlight" class="text-orange-500 font-black mx-0.5">{{
                                                s.highlight
                                            }}</span
                                            >{{ s.tail }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-start gap-3 bg-[#EBF2FF] rounded-xl px-4 py-3">
                    <span class="flex-shrink-0 mt-0.5">
                        <Icon name="el-icon-InfoFilled" color="#0065fb" :size="15" />
                    </span>
                    <span class="text-xs text-slate-500 leading-relaxed">
                        上传前请确保素材符合以上规格，不符合要求的文件将无法正常处理。
                    </span>
                </div>
            </div>

            <div
                class="px-6 py-4 border-t border-slate-100 flex justify-end shrink-0 bg-white shadow-[0_-10px_20px_rgba(0,0,0,0.02)]">
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
const emit = defineEmits<{
    (e: "close"): void;
}>();

const popupRef = shallowRef();

const VIDEO_RULES = [
    { label: "格式", value: "mp4、mov" },
    { label: "数量与时长", value: "单视频 2~15s，最多传 3 个，总时长不超过 15s" },
    { label: "大小", value: "单个视频不超过 50 MB" },
    { label: "尺寸", value: "宽高比 0.4 ~ 2.5，边长 300 ~ 6000px" },
    { label: "总像素", value: "409,600 ~ 927,408 之间 (如 480p, 720p)" },
    { label: "帧率", value: "24 ~ 60 FPS" },
];

const IMAGE_RULES = [
    { label: "格式", value: "jpeg, png, webp, bmp, tiff, gif" },
    { label: "大小", value: "单张 < 30 MB" },
    { label: "尺寸", value: "宽高比 0.4 ~ 2.5，边长 300 ~ 6000px" },
    {
        label: "数量限制",
        value: "",
        sub: [
            { text: "参考生成：1 ~ 9 张", highlight: "", tail: "" },
            { text: "首尾帧过渡：首帧与尾帧均为", highlight: "必填项", tail: "，必须同时上传。" },
        ],
    },
];

const open = () => popupRef.value?.open();

const close = () => emit("close");

defineExpose({ open, close });
</script>

<style scoped></style>
