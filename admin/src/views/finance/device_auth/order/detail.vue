<template>
    <div class="detail-popup">
        <popup
            ref="popupRef"
            title="订单详情"
            width="760px"
            :cancelButtonText="''"
            confirmButtonText="关闭"
            @confirm="handleConfirm"
            @close="handleClose"
        >
            <el-descriptions :column="2" border v-loading="loading">
                <el-descriptions-item label="订单编号">{{ detail.sn || '-' }}</el-descriptions-item>
                <el-descriptions-item label="业务类型">{{
                    detail.biz_type_desc || '-'
                }}</el-descriptions-item>
                <el-descriptions-item label="用户昵称">{{
                    detail.nickname || '-'
                }}</el-descriptions-item>
                <el-descriptions-item label="手机号">{{
                    detail.mobile || '-'
                }}</el-descriptions-item>
                <el-descriptions-item label="套餐">{{
                    detail.auth_type_desc || '-'
                }}</el-descriptions-item>
                <el-descriptions-item label="数量">{{
                    detail.quantity ?? '-'
                }}</el-descriptions-item>
                <el-descriptions-item label="订单金额(元)">{{
                    detail.order_amount ?? '-'
                }}</el-descriptions-item>
                <el-descriptions-item label="算力金额">{{
                    detail.tokens_amount ?? '-'
                }}</el-descriptions-item>
                <el-descriptions-item label="支付类型">{{
                    detail.pay_type_desc || '-'
                }}</el-descriptions-item>
                <el-descriptions-item label="支付方式">{{
                    detail.pay_way_desc || '-'
                }}</el-descriptions-item>
                <el-descriptions-item label="支付状态">
                    <el-tag
                        :type="detail.pay_status === DeviceAuthPayStatus.PAID ? 'success' : 'info'"
                    >
                        {{ detail.pay_status_desc || '-' }}
                    </el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="支付时间">{{
                    detail.pay_time || '-'
                }}</el-descriptions-item>
                <el-descriptions-item label="下单时间">{{
                    detail.create_time || '-'
                }}</el-descriptions-item>
            </el-descriptions>

            <div class="font-medium mt-5 mb-3">CDK明细</div>
            <el-table size="default" :data="detail.codes || []" max-height="320">
                <el-table-column label="ID" prop="id" width="80" />
                <el-table-column label="CDK" prop="code" min-width="200" show-overflow-tooltip />
                <el-table-column label="套餐" prop="type_desc" min-width="100" />
                <el-table-column label="状态" min-width="100">
                    <template #default="{ row }">
                        <el-tag
                            :type="row.status === DeviceAuthCodeStatus.USED ? 'info' : 'success'"
                        >
                            {{ row.status_desc }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="使用设备" min-width="160" show-overflow-tooltip>
                    <template #default="{ row }">{{ row.device_code || '-' }}</template>
                </el-table-column>
                <el-table-column label="使用时间" min-width="180">
                    <template #default="{ row }">{{ row.use_time || '-' }}</template>
                </el-table-column>
            </el-table>
        </popup>
    </div>
</template>

<script lang="ts" setup>
import Popup from '@/components/popup/index.vue'
import { deviceAuthOrderDetail } from '@/api/ai_application/device_auth'
import { DeviceAuthCodeStatus, DeviceAuthPayStatus } from '@/enums/deviceAuthEnums'

const emit = defineEmits(['close'])

const popupRef = shallowRef<InstanceType<typeof Popup>>()
const loading = ref(false)
const detail = reactive<Record<string, any>>({ codes: [] })

const getDetail = async (id: number) => {
    loading.value = true
    try {
        const data = await deviceAuthOrderDetail({ id })
        Object.assign(detail, data)
    } finally {
        loading.value = false
    }
}

const handleConfirm = () => {
    popupRef.value?.close()
}

const handleClose = () => {
    emit('close')
}

const open = (id: number) => {
    popupRef.value?.open()
    getDetail(id)
}

defineExpose({ open })
</script>
