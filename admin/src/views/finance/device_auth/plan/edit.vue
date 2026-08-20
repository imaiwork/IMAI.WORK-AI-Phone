<template>
    <div class="edit-popup">
        <popup
            ref="popupRef"
            :title="popupTitle"
            :async="true"
            width="580px"
            :confirmLoading="isLock"
            @confirm="lockFn"
            @close="handleClose"
        >
            <el-form ref="formRef" :rules="rules" :model="formData" label-width="100px">
                <el-form-item label="套餐名称" prop="name">
                    <el-input v-model="formData.name" placeholder="请输入套餐名称" maxlength="20" />
                </el-form-item>
                <el-form-item label="套餐类型" prop="type">
                    <el-select v-model="formData.type" class="w-full" placeholder="请选择套餐类型">
                        <el-option
                            v-for="item in DEVICE_AUTH_PLAN_TYPE"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="有效天数" prop="duration_days">
                    <div class="flex-1">
                        <el-input
                            v-model="formData.duration_days"
                            v-number-input="{ min: 0, max: 99999 }"
                            placeholder="请输入有效天数"
                        />
                        <div class="form-tips">0 表示永久有效</div>
                    </div>
                </el-form-item>
                <el-form-item label="价格(元)" prop="price">
                    <div class="flex-1">
                        <el-input
                            v-model="formData.price"
                            v-number-input="{ min: 0, max: 999999, decimal: 2 }"
                            placeholder="请输入价格"
                        />
                        <div v-if="isVirtualPay" class="form-tips">
                            当前为小程序虚拟支付：此处价格（元）须与微信小程序后台「虚拟支付 → 道具」中该道具售价保持一致（微信侧单位为分，如 100 元对应 10000 分）；修改后请同步更新微信道具定价并重新发布，否则会支付失败。
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="算力价格" prop="tokens_price">
                    <el-input
                        v-model="formData.tokens_price"
                        v-number-input="{ min: 0, max: 99999999 }"
                        placeholder="请输入算力价格"
                    />
                </el-form-item>
                <el-form-item
                    v-if="isVirtualPay"
                    label="虚拟支付产品ID"
                    prop="product_id"
                    required
                >
                    <div class="flex-1">
                        <el-input
                            v-model="formData.product_id"
                            maxlength="64"
                            clearable
                            placeholder="请输入微信小程序虚拟支付产品ID"
                        />
                        <div class="form-tips">
                            需与小程序后台「虚拟支付 → 道具」中的 productId 完全一致；上方价格也必须与该道具售价同步
                        </div>
                    </div>
                </el-form-item>
                <el-form-item label="排序" prop="sort">
                    <div class="flex-1">
                        <el-input
                            v-model="formData.sort"
                            v-number-input="{ min: 0, max: 99999 }"
                            placeholder="请输入排序"
                        />
                        <div class="form-tips">数值越小越靠前</div>
                    </div>
                </el-form-item>
                <el-form-item label="推荐" prop="is_recommend">
                    <el-switch
                        v-model="formData.is_recommend"
                        :active-value="1"
                        :inactive-value="0"
                    />
                </el-form-item>
                <el-form-item label="状态" prop="status">
                    <el-switch
                        v-model="formData.status"
                        :active-value="DeviceAuthPlanStatus.ENABLED"
                        :inactive-value="DeviceAuthPlanStatus.DISABLED"
                    />
                </el-form-item>
                <el-form-item label="备注" prop="remark">
                    <el-input
                        v-model="formData.remark"
                        type="textarea"
                        :autosize="{ minRows: 3, maxRows: 5 }"
                        placeholder="请输入备注"
                        maxlength="200"
                        show-word-limit
                    />
                </el-form-item>
            </el-form>
        </popup>
    </div>
</template>

<script lang="ts" setup>
import type { FormInstance } from 'element-plus'
import Popup from '@/components/popup/index.vue'
import {
    deviceAuthPlanAdd,
    deviceAuthPlanEdit,
    deviceAuthPlanDetail
} from '@/api/ai_application/device_auth'
import { getPayConfig, getPayConfigLists } from '@/api/setting/pay'
import { DEVICE_AUTH_PLAN_TYPE, DeviceAuthPlanStatus } from '@/enums/deviceAuthEnums'
import { useLockFn } from '@/hooks/useLockFn'

const WECHAT_PAY_WAY = 2
const MNP_PAY_TYPE_VIRTUAL = 2

const emit = defineEmits(['success', 'close'])

const formRef = shallowRef<FormInstance>()
const popupRef = shallowRef<InstanceType<typeof Popup>>()

const mode = ref<'add' | 'edit'>('add')
const editId = ref<number>()
const isVirtualPay = ref(false)
const popupTitle = computed(() => (mode.value === 'edit' ? '编辑套餐' : '新增套餐'))

const defaultForm = () => ({
    name: '',
    type: '' as number | string,
    duration_days: '',
    price: '',
    tokens_price: '',
    product_id: '',
    is_recommend: 0,
    sort: 0,
    status: DeviceAuthPlanStatus.ENABLED as number,
    remark: ''
})

const formData = reactive<Record<string, any>>(defaultForm())

const rules = computed(() => ({
    name: [{ required: true, message: '请输入套餐名称', trigger: ['blur'] }],
    type: [{ required: true, message: '请选择套餐类型', trigger: ['change'] }],
    duration_days: [{ required: true, message: '请输入有效天数', trigger: ['blur'] }],
    price: [{ required: true, message: '请输入价格', trigger: ['blur'] }],
    tokens_price: [{ required: true, message: '请输入算力价格', trigger: ['blur'] }],
    product_id: [
        {
            validator: (_rule: any, value: any, callback: (err?: Error) => void) => {
                if (!isVirtualPay.value) {
                    callback()
                    return
                }
                if (value === undefined || value === null || String(value).trim() === '') {
                    callback(new Error('请输入虚拟支付产品ID'))
                    return
                }
                callback()
            },
            trigger: ['blur', 'change']
        }
    ]
}))

const loadVirtualPayFlag = async () => {
    try {
        const { lists } = await getPayConfigLists()
        const wechat = (lists || []).find((item: any) => Number(item.pay_way) === WECHAT_PAY_WAY)
        if (!wechat?.id) {
            isVirtualPay.value = false
            return
        }
        const detail = await getPayConfig({ id: wechat.id })
        isVirtualPay.value = Number(detail?.config?.mnp_pay_type) === MNP_PAY_TYPE_VIRTUAL
    } catch {
        isVirtualPay.value = false
    }
}

const getDetail = async (id: number) => {
    const data = await deviceAuthPlanDetail({ id })
    Object.keys(defaultForm()).forEach((key) => {
        if (data[key] !== undefined && data[key] !== null) {
            formData[key] = data[key]
        }
    })
}

const handleSubmit = async () => {
    await formRef.value?.validate()
    const payload = {
        ...formData,
        price: Number(formData.price),
        tokens_price: Number(formData.tokens_price),
        duration_days: Number(formData.duration_days),
        sort: Number(formData.sort),
        product_id: String(formData.product_id || '').trim()
    }
    if (mode.value === 'edit') {
        await deviceAuthPlanEdit({ ...payload, id: editId.value })
    } else {
        await deviceAuthPlanAdd(payload)
    }
    popupRef.value?.close()
    emit('success')
}

const { lockFn, isLock } = useLockFn(handleSubmit)

const handleClose = () => {
    emit('close')
}

const open = async (type: 'add' | 'edit', id?: number) => {
    mode.value = type
    Object.assign(formData, defaultForm())
    popupRef.value?.open()
    await loadVirtualPayFlag()
    if (type === 'edit' && id) {
        editId.value = id
        await getDetail(id)
    }
}

defineExpose({ open })
</script>
