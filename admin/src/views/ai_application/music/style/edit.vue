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
            <el-form ref="formRef" :model="formData" label-width="84px" :rules="rules">
                <el-form-item label="风格封面" prop="image">
                    <div>
                        <material-picker v-model="formData.image" :limit="1" />
                        <div class="form-tips">建议尺寸：400*400px</div>
                    </div>
                </el-form-item>
                <el-form-item label="风格名称" prop="name">
                    <el-input placeholder="请输入风格名称" v-model="formData.name" />
                </el-form-item>
                <el-form-item label="英文名称" prop="value">
                    <el-input placeholder="请输入英文名称" v-model="formData.value" />
                </el-form-item>

                <el-form-item label="排序">
                    <div class="flex-1">
                        <el-input class="ls-input" v-model="formData.sort" />
                        <div class="form-tips">默认为0，数值越大排越前面</div>
                    </div>
                </el-form-item>
                <!-- 状态 -->
                <el-form-item label="状态" required>
                    <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </el-form>
        </popup>
    </div>
</template>
<script lang="ts" setup>
import type { FormInstance } from 'element-plus'
import Popup from '@/components/popup/index.vue'
import { postMusicStyle, putMusicStyle } from '@/api/ai_application/music'
const emit = defineEmits(['success', 'close'])
const formRef = shallowRef<FormInstance>()
const popupRef = shallowRef<InstanceType<typeof Popup>>()
const mode = ref('add')
const popupTitle = computed(() => {
    return mode.value == 'add' ? '新增风格' : '编辑风格'
})
const formData = reactive({
    image: '',
    name: '',
    value: '',
    sort: 0,
    status: 1,
    id: ''
})

//校验规则
const rules = {
    image: [
        {
            required: true,
            message: '请选择风格封面'
        }
    ],
    name: [
        {
            required: true,
            message: '请输入名称',
            trigger: ['blur']
        }
    ],
    value: [
        {
            required: true,
            message: '请输入英文名称',
            trigger: ['blur']
        }
    ]
}

const handleSubmit = async () => {
    await formRef.value?.validate()
    mode.value == 'edit' ? await putMusicStyle(formData) : await postMusicStyle(formData)
    popupRef.value?.close()
    emit('success')
}

const open = (type = 'add') => {
    mode.value = type
    popupRef.value?.open()
}

const setFormData = async (row: any) => {
    for (const key in formData) {
        if (row[key] != null && row[key] != undefined) {
            //@ts-ignore
            formData[key] = row[key]
        }
    }
}

const handleClose = () => {
    emit('close')
}

defineExpose({
    open,
    setFormData
})
</script>
