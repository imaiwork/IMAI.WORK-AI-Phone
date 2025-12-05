<template>
    <popup
        ref="popupRef"
        :title="`调整${getTitleFromType}数量`"
        width="500px"
        @confirm="handleConfirm"
        :async="true"
        @close="popupClose"
    >
        <div class="pr-8">
            <el-form
                ref="formRef"
                :model="formData"
                label-width="120px"
                :rules="formRules"
                @submit.prevent
            >
                <el-form-item :label="`当前${getTitleFromType}`"
                    >{{ value }}（ 已使用 {{ use }} 个{{ getTitleFromType }}）
                </el-form-item>
                <el-form-item :label="`${getTitleFromType}增减`" required prop="action">
                    <el-radio-group v-model="formData.action">
                        <el-radio :label="1">增加数量</el-radio>
                        <el-radio :label="2">扣减数量</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item label="调整个数" prop="num">
                    <el-input
                        :model-value="formData.num"
                        placeholder="请输入调整的个数"
                        type="number"
                        @input="numberValidate"
                    />
                </el-form-item>
                <el-form-item :label="`调整后${getTitleFromType}`">
                    {{ adjustmentMoney.toFixed(2) }}个
                </el-form-item>
                <!-- <el-form-item label="备注" prop="remark">
                    <el-input v-model="formData.remark" type="textarea" :rows="4" />
                </el-form-item> -->
            </el-form>
        </div>
    </popup>
</template>
<script lang="ts" setup>
import Popup from '@/components/popup/index.vue'
import type { FormInstance, FormRules } from 'element-plus'
import feedback from '@/utils/feedback'
const formRef = shallowRef<FormInstance>()
const props = defineProps({
    show: {
        type: Boolean,
        required: true
    },
    type: {
        type: String,
        default: 'robot'
    },
    value: {
        type: [Number, String],
        required: true
    },
    use: {
        type: Number
    }
})
const emit = defineEmits<{
    (event: 'update:show', value: boolean): void
    (event: 'confirm', value: any): void
}>()
const formData = reactive({
    action: 1, //变动类型 1-增加 2-减少
    num: '',
    remark: ''
})

const getTitleFromType = computed(() => {
    switch (props.type) {
        case 'robot':
            return '机器人'
        case 'kb':
            return '知识库'
    }
})
const popupRef = shallowRef<InstanceType<typeof Popup>>()

const adjustmentMoney = computed(() => {
    return Number(props.value) + Number(formData.num) * (formData.action == 1 ? 1 : -1)
})

const formRules: FormRules = {
    num: [
        {
            required: true,
            message: '请输入调整的数量'
        }
    ]
}
const numberValidate = (value: string) => {
    if (value.includes('-')) {
        return feedback.msgError('请输入正整数')
    }
    formData.num = value
}
const handleConfirm = async () => {
    await formRef.value?.validate()
    emit('confirm', formData)
}

const popupClose = () => {
    emit('update:show', false)
    formRef.value?.resetFields()
}
watch(
    () => props.show,
    (val) => {
        if (val) {
            popupRef.value?.open()
        } else {
            popupRef.value?.close()
        }
    }
)
watch(adjustmentMoney, (val) => {
    if (val < 0) {
        feedback.msgError('调整后数量需大于0')
        formData.num = ''
    }
})
</script>
