<template>
    <div class="toolbar-right flex items-center gap-2">
        <!-- 地图模式：历史 -->
        <ElTooltip v-if="activeMode === 'map'" content="历史" placement="top">
            <div class="toolbar-btn icon-only" @click="emit('openMapHistory')">
                <Icon name="el-icon-Clock" :size="14" />
            </div>
        </ElTooltip>

        <!-- 图片：优秀案例库 -->
        <ElTooltip v-if="activeMode === 'image'" content="优秀案例" placement="top">
            <div class="toolbar-btn icon-only" @click.stop="emit('openImageCaseLibrary')">
                <Icon name="el-icon-Opportunity" :size="14" />
            </div>
        </ElTooltip>

        <!-- 图片 / PPT / 视频 / 数字人：历史会话 -->
        <div
            v-if="['image', 'ppt', 'video', 'digital'].includes(activeMode)"
            ref="historyAnchorRef"
            class="relative">
            <ElTooltip content="历史" placement="top">
                <div class="toolbar-btn icon-only" @click.stop="emit('openHistory')">
                    <Icon name="el-icon-Clock" :size="14" />
                </div>
            </ElTooltip>
        </div>

        <!-- 文件上传（地图模式不显示;数字人模式仅"数字人视频"不显示） -->
        <ElTooltip v-if="showFileUpload" :content="uploadButtonLabel" placement="top">
            <div>
                <FileUpload
                    :model-value="fileList"
                    :file-limit="fileLimit"
                    :accept="uploadAccept"
                    @update:model-value="emit('update:fileList', $event)"
                    @change="onFileChange">
                    <div class="toolbar-btn dashed icon-only">
                        <Icon name="el-icon-Paperclip" :size="14" />
                    </div>
                </FileUpload>
            </div>
        </ElTooltip>
    </div>
</template>

<script setup lang="ts">
import FileUpload from '@/components/chatting/file-upload.vue'
import type { FileParams } from '@/composables/usePasteImage'
import { IMAGE_REF_MAX } from '../../_enums/welcome-toolbar'

const props = defineProps<{
    activeMode: string
    showFileUpload: boolean
    uploadButtonLabel: string
    uploadAccept: string
    fileList: FileParams[]
}>()

const emit = defineEmits<{
    openMapHistory: []
    openImageCaseLibrary: []
    openHistory: []
    'update:fileList': [value: FileParams[]]
    syncFiles: [list: FileParams[]]
}>()

const historyAnchorRef = ref<HTMLElement | null>(null)

/** 图片模式对齐小程序 3 张；其它模式保持原 9 */
const fileLimit = computed(() => (props.activeMode === 'image' ? IMAGE_REF_MAX : 9))

function onFileChange(list: FileParams[]) {
    emit('syncFiles', list)
}

defineExpose({
    getHistoryAnchor: () => historyAnchorRef.value
})
</script>

<style lang="scss" scoped>
.toolbar-right {
    .toolbar-btn.icon-only {
        @apply inline-flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-full border border-[#ebedf0] bg-white p-0 text-[#4b5563];

        &:hover {
            @apply border-[#93c5fd] text-[#2563eb];
        }
        &.dashed {
            @apply border-dashed;
        }
    }
}
</style>
