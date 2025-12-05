<template>
    <div class="edit-popup">
        <popup
            ref="popupRef"
            title="批量导入"
            :async="true"
            width="640px"
            confirm-button-text="开始导入"
            @confirm="handleSubmit"
            @close="handleClose"
        >
            <el-upload
                ref="uploadRef"
                :drag="true"
                :headers="headers"
                :limit="1"
                :action="action"
                :multiple="false"
                :auto-upload="false"
                :on-progress="handleProgress"
                :on-error="handleError"
                :on-success="handleSuccess"
            >
                <div class="el-upload__text py-[13px]">
                    拖拽文件至此，或点击👉🏻<span class="text-primary">选择文件</span>
                    <div>支持 .xls、xlsx格式</div>
                </div>
                <template #tip>
                    <el-button
                        class="mt-4"
                        type="primary"
                        :link="true"
                        :icon="Download"
                        @click="handleDownLoad"
                    >
                        下载批量导入模版
                    </el-button>
                </template>
            </el-upload>
        </popup>
    </div>
</template>
<script lang="ts" setup>
import type { UploadInstance } from 'element-plus'
import { Download } from '@element-plus/icons-vue'
import Popup from '@/components/popup/index.vue'
import useUserStore from '@/stores/modules/user'
import config from '@/config'
import feedback from '@/utils/feedback'
import { RequestCodeEnum } from '@/enums/requestEnums'

const userStore = useUserStore()

const emit = defineEmits(['success', 'close'])
const uploadRef = ref<UploadInstance>()
const popupRef = shallowRef<InstanceType<typeof Popup>>()

const action = ref(`${config.baseUrl}${config.urlPrefix}/creation.creationModel/import`)
const downloadTemplateURI = ref(`${config.baseUrl}${config.urlPrefix}/creation.creationModel/downExcelTemplate`)
const headers = computed(() => ({
    token: userStore.token,
    version: config.version
}))

const handleDownLoad = async () => {
    window.open(downloadTemplateURI.value)
}

const handleProgress = () => {
    feedback.loading('导入中...')
}

const handleSuccess = (response: any) => {
    if (response.code == RequestCodeEnum.FAIL && response.msg) {
        feedback.msgError(response.msg)
    }
    if (response.code === 1) {
        feedback.msgSuccess(response.msg)
    }
    feedback.closeLoading()
    emit('success')
    popupRef.value?.close()
    uploadRef.value?.clearFiles()
}

const handleError = () => {
    feedback.closeLoading()
}

//提交
const handleSubmit = async () => {
    uploadRef.value!.submit()
}

const handleClose = () => {
    emit('close')
}

const open = () => {
    popupRef.value?.open()
}

defineExpose({
    open
})
</script>
