<template>
    <div class="edit-popup">
        <popup
            ref="popupRef"
            :title="popupTitle"
            :async="true"
            width="550px"
            @confirm="handleSubmit"
            @close="handleClose"
        >
            <el-form ref="formRef" :model="formData" :rules="formRules" label-width="80px">
                <el-form-item label="敏感词语" prop="word">
                    <div>
                        <el-input
                            class="w-[340px]"
                            type="textarea"
                            :autosize="{ minRows: 6, maxRows: 4 }"
                            v-model="formData.word"
                            :placeholder="`如：领袖\n政治体系\n政治事件`"
                        />
                        <div class="form-tips">输入敏感词语，添加多个按enter回车换行</div>
                    </div>
                </el-form-item>
                <el-form-item label="敏感状态">
                    <el-radio-group v-model="formData.status">
                        <el-radio :label="1">启用</el-radio>
                        <el-radio :label="0">停用</el-radio>
                    </el-radio-group>
                </el-form-item>
            </el-form>
        </popup>
    </div>
</template>
<script setup lang="ts">
import type { FormInstance, FormRules } from 'element-plus'
import Popup from '@/components/popup/index.vue'
import { addSensitive, editSensitive } from '@/api/ai_setting/sensitive'
const formData = ref({
    id: '',
    word: '',
    status: 1
})
const formRules: FormRules = {
    word: [
        {
            required: true,
            message: '请输入敏感词语'
        }
    ]
}
const emit = defineEmits(['success', 'close'])
const formRef = shallowRef<FormInstance>()
const popupRef = shallowRef<InstanceType<typeof Popup>>()
const mode = ref('add')
const popupTitle = computed(() => {
    return mode.value == 'edit' ? '编辑敏感词' : '自定义敏感词'
})
const open = (type = 'add') => {
    mode.value = type
    popupRef.value?.open()
}
const handleSubmit = async () => {
    await formRef.value?.validate()
    const params = {
        ...formData.value,
        word: formData.value.word.split('\n').filter(Boolean)
    }
    mode.value == 'add' ? await addSensitive(params) : await editSensitive(params)
    popupRef.value?.close()
    emit('close')
}
const handleClose = () => {
    emit('close')
}
const setFormData = (data: Record<any, any>) => {
    for (const key in formData.value) {
        if (data[key] != null && data[key] != undefined) {
            //@ts-ignore
            formData.value[key] = data[key]
        }
    }
    formData.value.word = data.word_arr.join('\n')
}
defineExpose({
    open,
    setFormData
    // getDetail
})
</script>
