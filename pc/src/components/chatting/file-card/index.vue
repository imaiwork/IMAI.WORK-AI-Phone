<template>
    <div
        :style="{
            '--elx-files-card-icon-size': `${_iconSize}`,
            '--elx-files-card-max-width': `${props.maxWidth}`,
            ...props.style,
            ...(isHovered && props.hoverStyle ? props.hoverStyle : {}),
        }"
        class="elx-files-card"
        :class="{
            'elx-files-card-square': isSquareVariant && isImageFile,
            'elx-files-card-hovered': isHovered,
            'elx-files-card-error': props.status === UPLOAD_STATUS.ERROR,
            'elx-files-card-done': props.status === UPLOAD_STATUS.SUCCESS,
            'elx-files-card-uploading': props.status === UPLOAD_STATUS.UPLOADING,
        }"
        @mouseenter="isHovered = true"
        @mouseleave="isHovered = false">
        <div
            v-if="props.status === UPLOAD_STATUS.UPLOADING && !(isSquareVariant && isImageFile)"
            class="elx-files-card-progress"
            :style="{ width: `${props.percent || 0}%` }" />

        <slot v-if="$slots.icon || _fileType" name="icon" v-bind="{ item: props }">
            <component
                :is="svgIconMap[_fileType!]"
                v-if="!isImageFile"
                class="elx-files-card-icon"
                :color="props.iconColor || colorMap[_fileType!]" />
            <!-- 图片文件处理区域（新增遮罩层逻辑） -->
            <template v-if="isImageFile">
                <div
                    class="image-preview-container"
                    :class="{
                        'image-preview-container-square': isSquareVariant && isImageFile,
                    }"
                    @mouseenter="imageHovered = true"
                    @mouseleave="imageHovered = false">
                    <ElImage
                        v-if="_previewImgUrl"
                        ref="imgRef"
                        class="elx-files-card-img"
                        :src="_previewImgUrl"
                        :preview-src-list="props.imgPreview ? [_previewImgUrl] : []"
                        fit="cover"
                        :show-progress="false"
                        hide-on-click-modal
                        @show="handlePreviewAction('self')" />

                    <!-- 无预览地址时显示默认图标 -->
                    <component
                        :is="svgIconMap[_fileType!]"
                        v-else
                        class="elx-files-card-img"
                        :color="props.iconColor || colorMap[_fileType!]" />

                    <!-- 遮罩层 -->
                    <!-- 悬停时才展示 -->
                    <transition name="fade">
                        <slot
                            v-if="imageHovered && _previewImgUrl && props.imgPreviewMask && props.imgPreview"
                            name="image-preview-actions"
                            v-bind="{
                                item: { ...props, prefix: namePrefix },
                            }">
                            <div class="preview-mask" @click="handlePreviewAction('mask')">
                                <Icon name="el-icon-View"></Icon>
                                <span>预览</span>
                            </div>
                        </slot>
                    </transition>

                    <!-- 如果有状态，不悬停，也展示 -->
                    <!-- 状态判断显示区域 -->
                    <div
                        v-if="props.status === UPLOAD_STATUS.UPLOADING && isSquareVariant"
                        class="preview-mask-loading">
                        <ElProgress color="#fff" type="circle" :percentage="props.percent" class="circle-progress" />
                    </div>
                    <div v-else-if="props.status === UPLOAD_STATUS.ERROR && isSquareVariant" class="preview-mask-error">
                        <span class="error-text">{{ props.errorTip || "上传失败" }}</span>
                    </div>
                </div>
            </template>
        </slot>

        <slot
            v-if="($slots.content || props.name || description) && !(isSquareVariant && isImageFile)"
            name="content"
            v-bind="{ item: props }">
            <div class="elx-files-card-content">
                <div v-if="props.name" class="elx-files-card-name">
                    <slot
                        name="name-prefix"
                        v-bind="{
                            item: { ...props, prefix: namePrefix },
                        }">
                        <div class="elx-files-card-name-prefix">
                            {{ namePrefix }}
                        </div>
                    </slot>
                </div>
                <slot
                    name="description"
                    v-bind="{
                        item: { ...props, prefix: namePrefix },
                    }">
                    <div
                        class="elx-files-card-description"
                        :class="{
                            'elx-files-card-description-error': props.status === 'error',
                            'elx-files-card-description-done': props.status === 'done',
                            'elx-files-card-description-uploading': props.status === 'uploading',
                        }">
                        {{ _description }}
                    </div>
                </slot>
            </div>
        </slot>

        <div v-if="props.showDelIcon && isHovered" class="elx-files-card-delete-icon" @click="handleDelete">
            <slot name="del-icon" v-bind="{ item: props }">
                <Icon name="el-icon-CircleCloseFilled"></Icon>
            </slot>
        </div>
    </div>
</template>
<script setup lang="ts">
import type { FilesCardEmits, FilesCardProps } from "./types.js";
import { getFileType, getSize, previewImage } from "./index";
import svgIconMap from "./file-svg/index";
import { colorMap } from "./options.js";
import { UPLOAD_STATUS } from "@/composables/usePasteImage";

const props = withDefaults(defineProps<FilesCardProps>(), {
    uid: undefined,
    name: undefined,
    fileSize: undefined,
    fileType: undefined,
    description: undefined,
    url: undefined,
    thumbUrl: undefined,
    imgFile: undefined,
    iconSize: "42px",
    iconColor: undefined,
    showDelIcon: false,
    maxWidth: "236px",
    style: undefined,
    hoverStyle: undefined,
    imgVariant: "rectangle",
    imgPreview: true,
    imgPreviewMask: true,
    status: undefined,
    percent: undefined,
    errorTip: undefined,
});

const emits = defineEmits<FilesCardEmits>(); // 新增自定义事件
const { name, fileType, description, url, thumbUrl, fileSize, imgVariant } = toRefs(props);

const namePrefix = computed(() => {
    const nameStr = name.value || "";
    const lastDotIndex = nameStr.lastIndexOf(".");
    return lastDotIndex === -1 ? nameStr : nameStr.slice(0, lastDotIndex);
});

const nameSuffix = computed(() => {
    const nameStr = name.value || "";
    const lastDotIndex = nameStr.lastIndexOf(".");
    if (lastDotIndex === -1 && nameStr.length - lastDotIndex > 10) {
        // 文件名长度超过10个字符 显示.file
        return ".file";
    }
    return lastDotIndex === -1 ? "" : nameStr.slice(lastDotIndex);
});
const isHovered = ref(false);
const imageHovered = ref(false); // 新增图片悬停状态

/* 图片类型文件预览 开始 */
const _previewImg = ref<string | undefined>(undefined);
/* 图片类型文件预览 结束 */

const _fileType = computed(() => {
    if (fileType.value) return fileType.value;

    if (!name.value) return undefined;
    if (!nameSuffix.value) {
        return "unknown";
    }
    return getFileType(nameSuffix.value).lowerCase;
});

const _fileTypeUpperCase = computed(() => {
    if (fileType.value) return fileType.value.replace(/^\w/, (match) => match.toUpperCase());
    if (!name.value) return "";
    if (!nameSuffix.value) {
        return "Unknown";
    }
    return getFileType(nameSuffix.value).upperCase;
});

const _description = computed(() => {
    if (description.value) {
        return description.value;
    }
    const typeStr = _fileTypeUpperCase.value;
    const sizeStr = fileSize.value ? `・${getSize(fileSize.value)}` : "";
    if (props.status === UPLOAD_STATUS.UPLOADING) {
        return `上传中...${`・${props.percent || 0}`}%${sizeStr}`;
    }
    if (props.status === UPLOAD_STATUS.ERROR) {
        return props.errorTip || "上传失败";
    }
    return `${typeStr}${sizeStr}`;
});

const isImageFile = computed(() => _fileType.value === "image");
const isSquareVariant = computed(() => imgVariant.value === "square");
const _previewImgUrl = computed(() => {
    if (!isImageFile.value) return undefined;
    if (thumbUrl.value) return thumbUrl.value;
    if (url.value) return url.value;
    return _previewImg.value;
});

const _iconSize = computed(() => {
    if (
        (isSquareVariant.value && isImageFile.value && !props.iconSize) ||
        (isSquareVariant.value && isImageFile.value && props.iconSize === "42px")
    ) {
        return "64px";
    }
    return props.iconSize;
});

watch(
    () => props.imgFile,
    async (newFile) => {
        if (newFile) {
            try {
                const url = await previewImage(newFile);
                _previewImg.value = url;
            } catch (error) {
                console.error("Preview failed:", error);
            }
        } else {
            _previewImg.value = undefined;
        }
    },
    { deep: true, immediate: true }
);

function handleDelete() {
    emits("delete", { ...props });
}

// 遮罩展开时触发预览
const imgRef = ref();
function handlePreviewAction(type: "self" | "mask") {
    if (props.imgPreview && imgRef.value && _previewImgUrl && type === "mask") {
        imgRef.value!.showPreview();
    }
    if (type === "self") {
        emits("imagePreview", { ...props });
    }
}

defineExpose({
    namePrefix,
    nameSuffix,
    colorMap,
});
</script>

<style scoped lang="scss">
.elx-files-card {
    width: fit-content;
    padding: 12px;
    display: flex;
    flex-wrap: nowrap;
    gap: 8px;
    align-items: center;
    border-radius: 8px;
    position: relative;
    background: rgba(0, 0, 0, 0.06);
    max-width: var(--elx-files-card-max-width);

    .elx-files-card-progress {
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.08);
        border-radius: 8px;
        transition: width 0.2s ease;
    }

    .elx-files-card-delete-icon {
        position: absolute;
        top: -8px;
        right: -6px;
        cursor: pointer;
        color: rgba(0, 0, 0, 0.4);
        display: none;

        &:hover {
            color: #ff4d4f;
        }
    }

    &:hover .elx-files-card-delete-icon {
        display: block;
    }
}

.elx-files-card-square {
    padding: 0;
    margin: 0;
}

.elx-files-card-icon {
    flex: none;
    font-size: var(--elx-files-card-icon-size);
}

.elx-files-card-img {
    flex: none;
    width: var(--elx-files-card-icon-size);
    height: var(--elx-files-card-icon-size);
    border-radius: 8px;
    object-fit: cover;
}

.elx-files-card-content {
    flex: auto;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    justify-content: space-between;
    height: 100%;

    .elx-files-card-name {
        display: flex;
        flex-wrap: nowrap;
        max-width: 100%;
        font-size: 14px;

        .elx-files-card-name-prefix {
            flex: 0 1 auto;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    }

    .elx-files-card-description {
        flex: none;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        color: rgba(0, 0, 0, 0.4);
        font-size: 12px;
    }

    .elx-files-card-description-error {
        color: #ff4d4f;
    }
}

/* 新增遮罩层样式 */
.image-preview-container {
    position: relative;
    padding: 0px;
    width: var(--elx-files-card-icon-size);
    height: var(--elx-files-card-icon-size);
    // display: flex;
    display: inline-block;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s;
    flex-shrink: 0;

    .preview-mask {
        position: absolute;
        inset: 0;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        opacity: 0;
        background: rgba(0, 0, 0, 0.65);
        color: #fff;
        font-size: 10px;
        transition: opacity 0.3s;

        .el-icon {
            font-size: 10px;
            margin-right: 2px;
            display: flex;
            align-items: center;
            height: 100%;
            margin-top: 2px;
        }

        &:hover {
            opacity: 1;
            transition: opacity 0.3s;
            cursor: pointer;
        }
    }
}

.image-preview-container-square {
    .preview-mask {
        font-size: 14px;

        .el-icon {
            font-size: 12px;
        }
    }
}

// 正方形变体，加载中样式,不悬停直接展示
.preview-mask-loading {
    position: absolute;
    inset: 0;
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    background: rgba(0, 0, 0, 0.65);
    color: #fff;
    transition: all 0.3s;

    .circle-progress {
        width: 100% !important;
        height: 100% !important;
        display: flex;
        justify-content: center;
        align-items: center;

        :deep() {
            .el-progress-circle {
                width: calc(100% - 12px) !important;
                height: calc(100% - 12px) !important;

                svg > path:nth-child(1) {
                    stroke: rgba(255, 255, 255, 0.2);
                    stroke-width: 8px;
                }
            }
            .el-progress__text {
                color: #fff;
                font-size: 14px;
            }
        }
    }
}

// 正方形变体，加载中样式,不悬停直接展示
.preview-mask-error {
    position: absolute;
    inset: 0;
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    background: rgba(0, 0, 0, 0.5);
    font-size: 12px;
    transition: all 0.3s;
    color: #ff4d4f;
    // 溢出隐藏
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

:deep() {
    .el-image-viewer__progress {
        display: none;
    }
}

/* 过渡动画样式 */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
