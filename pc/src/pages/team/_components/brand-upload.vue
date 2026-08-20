<template>
    <Upload
        class="brand-upload"
        :class="[`is-${variant}`, { 'has-preview': !!preview }]"
        :type="variant === 'file' ? 'file' : 'image'"
        :accept="variant === 'file' ? '.zip' : undefined"
        :action="action"
        :image-resolution="
            variant === 'file'
                ? undefined
                : [
                      [1, 10000],
                      [1, 10000],
                  ]
        "
        :limit="1"
        :multiple="false"
        :show-file-list="false"
        :show-progress="true"
        @success="onSuccess">
        <div class="brand-upload__tile">
            <!-- 图片已上传：预览 + 悬停更换 -->
            <template v-if="preview && variant !== 'file'">
                <ElImage :src="preview" fit="cover" class="brand-upload__img" />
                <div class="brand-upload__mask">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-5 h-5">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                    <span>更换</span>
                </div>
            </template>
            <!-- 代码包已上传 -->
            <template v-else-if="variant === 'file' && done">
                <div class="brand-upload__done">
                    <div class="brand-upload__done-ic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </div>
                    <div class="text-[13px] font-bold text-emerald-600">{{ doneText || "已上传" }}</div>
                    <div class="text-[12px] text-slate-400 mt-0.5">点击可重新上传</div>
                </div>
            </template>
            <!-- 空态 -->
            <template v-else>
                <div class="brand-upload__icon">
                    <svg
                        v-if="variant === 'file'"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.6"
                        class="w-6 h-6">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m.75 12l3 3m0 0l3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <svg
                        v-else
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.6"
                        class="w-6 h-6">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                    </svg>
                </div>
                <div class="brand-upload__title">{{ title }}</div>
                <div v-if="hint" class="brand-upload__hint">{{ hint }}</div>
            </template>
        </div>
    </Upload>
</template>

<script setup lang="ts">
import Upload from "@/components/upload/index.vue";

withDefaults(
    defineProps<{
        /** icon | logo | qr | file */
        variant?: "icon" | "logo" | "qr" | "file";
        /** 图片预览地址 */
        preview?: string;
        /** 空态主文案 */
        title?: string;
        /** 空态提示(尺寸说明等) */
        hint?: string;
        /** 自定义上传 action(代码包用) */
        action?: string;
        /** 文件上传完成态 */
        done?: boolean;
        doneText?: string;
    }>(),
    {
        variant: "icon",
        title: "点击上传",
        hint: "",
        action: "",
        done: false,
        doneText: "",
    },
);

const emit = defineEmits<{ (e: "success", res: any): void }>();
const onSuccess = (res: any) => emit("success", res);
</script>

<style lang="scss" scoped>
.brand-upload {
    display: inline-block;
    width: 100%;

    :deep(.upload),
    :deep(.el-upload) {
        display: block;
        width: 100%;
    }
    :deep(.el-upload) {
        --el-upload-dragger-padding-horizontal: 0;
        --el-upload-dragger-padding-vertical: 0;
    }
}

.brand-upload__tile {
    position: relative;
    width: 100%;
    border-radius: 16px;
    border: 1.5px dashed #d7e0ea;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    overflow: hidden;
    cursor: pointer;
    transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    user-select: none;

    &:hover {
        border-color: rgba(0, 101, 251, 0.55);
        background: rgba(0, 101, 251, 0.04);
        box-shadow: 0 8px 20px -10px rgba(0, 101, 251, 0.35);
    }
}

.is-icon .brand-upload__tile {
    aspect-ratio: 1;
    min-height: 96px;
}
.is-logo .brand-upload__tile {
    aspect-ratio: 16 / 9;
    min-height: 96px;
}
.is-qr .brand-upload__tile {
    aspect-ratio: 1;
    min-height: 120px;
    max-width: 140px;
}
.is-file .brand-upload__tile {
    min-height: 112px;
    padding: 20px 16px;
}

.brand-upload__img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}

.brand-upload__mask {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    background: rgba(15, 23, 42, 0.48);
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    opacity: 0;
    transition: opacity 0.18s ease;
}
.brand-upload__tile:hover .brand-upload__mask {
    opacity: 1;
}

.brand-upload__icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    color: #0065fb;
    background: rgba(0, 101, 251, 0.1);
    transition: transform 0.2s ease;
}
.brand-upload__tile:hover .brand-upload__icon {
    transform: translateY(-2px);
}

.brand-upload__title {
    font-size: 13px;
    font-weight: 700;
    color: #334155;
}
.brand-upload__hint {
    font-size: 11px;
    color: #94a3b8;
    text-align: center;
    padding: 0 8px;
    line-height: 1.35;
}

.brand-upload__done {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}
.brand-upload__done-ic {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    color: #059669;
    background: rgba(16, 185, 129, 0.12);
    margin-bottom: 4px;
}

@media (prefers-reduced-motion: reduce) {
    .brand-upload__tile,
    .brand-upload__icon,
    .brand-upload__mask {
        transition: none;
    }
}
</style>
