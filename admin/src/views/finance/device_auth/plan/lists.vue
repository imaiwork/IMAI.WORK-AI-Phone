<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="套餐名称">
                    <el-input
                        class="w-[240px]"
                        v-model="queryParams.name"
                        placeholder="请输入套餐名称"
                        clearable
                        @keyup.enter="resetPage"
                    />
                </el-form-item>
                <el-form-item label="套餐类型">
                    <el-select
                        class="!w-[160px]"
                        v-model="queryParams.type"
                        placeholder="全部"
                        :empty-values="[undefined, null]"
                    >
                        <el-option label="全部" value="" />
                        <el-option
                            v-for="item in DEVICE_AUTH_PLAN_TYPE"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="状态">
                    <el-select
                        class="!w-[140px]"
                        v-model="queryParams.status"
                        placeholder="全部"
                        :empty-values="[undefined, null]"
                    >
                        <el-option label="全部" value="" />
                        <el-option label="启用" :value="DeviceAuthPlanStatus.ENABLED" />
                        <el-option label="禁用" :value="DeviceAuthPlanStatus.DISABLED" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card class="!border-none mt-4" shadow="never">
            <el-button
                v-perms="['deviceauth.deviceAuthPlan/add']"
                type="primary"
                class="mb-4"
                @click="handleAdd"
            >
                <template #icon>
                    <icon name="el-icon-Plus" />
                </template>
                新增套餐
            </el-button>

            <el-table size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="ID" prop="id" width="80" />
                <el-table-column label="套餐名称" prop="name" min-width="120" />
                <el-table-column label="类型" prop="type_desc" min-width="100" />
                <el-table-column label="有效天数" min-width="100">
                    <template #default="{ row }">
                        {{ Number(row.duration_days) > 0 ? `${row.duration_days} 天` : '永久' }}
                    </template>
                </el-table-column>
                <el-table-column label="价格(元)" prop="price" min-width="100" />
                <el-table-column label="算力价格" prop="tokens_price" min-width="100" />
                <el-table-column label="虚拟支付产品ID" min-width="160" show-overflow-tooltip>
                    <template #default="{ row }">{{ row.product_id || '-' }}</template>
                </el-table-column>
                <el-table-column label="推荐" min-width="90">
                    <template #default="{ row }">
                        <el-tag v-if="row.is_recommend" type="warning">推荐</el-tag>
                        <span v-else>-</span>
                    </template>
                </el-table-column>
                <el-table-column label="排序" prop="sort" min-width="80" />
                <el-table-column label="状态" min-width="100">
                    <template #default="{ row }">
                        <el-switch
                            v-perms="['deviceauth.deviceAuthPlan/changeStatus']"
                            :model-value="row.status"
                            :active-value="DeviceAuthPlanStatus.ENABLED"
                            :inactive-value="DeviceAuthPlanStatus.DISABLED"
                            @change="changeStatus(row)"
                        />
                    </template>
                </el-table-column>
                <el-table-column label="备注" prop="remark" min-width="120" show-overflow-tooltip>
                    <template #default="{ row }">{{ row.remark || '-' }}</template>
                </el-table-column>
                <el-table-column label="操作" width="150" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['deviceauth.deviceAuthPlan/edit']"
                            type="primary"
                            link
                            @click="handleEdit(row)"
                        >
                            编辑
                        </el-button>
                        <el-button
                            v-perms="['deviceauth.deviceAuthPlan/delete']"
                            type="danger"
                            link
                            @click="handleDelete(row.id)"
                        >
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>

        <edit-popup v-if="showEdit" ref="editRef" @success="getLists" @close="showEdit = false" />
    </div>
</template>

<script lang="ts" setup name="deviceAuthPlanLists">
import { usePaging } from '@/hooks/usePaging'
import {
    deviceAuthPlanLists,
    deviceAuthPlanDelete,
    deviceAuthPlanChangeStatus
} from '@/api/ai_application/device_auth'
import { DEVICE_AUTH_PLAN_TYPE, DeviceAuthPlanStatus } from '@/enums/deviceAuthEnums'
import feedback from '@/utils/feedback'
import EditPopup from './edit.vue'

const queryParams = reactive({
    name: '',
    type: '',
    status: '' as number | string
})

const showEdit = ref(false)
const editRef = shallowRef<InstanceType<typeof EditPopup>>()

const handleAdd = async () => {
    showEdit.value = true
    await nextTick()
    editRef.value?.open('add')
}

const handleEdit = async (row: any) => {
    showEdit.value = true
    await nextTick()
    editRef.value?.open('edit', row.id)
}

const handleDelete = async (id: number) => {
    await feedback.confirm('确定要删除该套餐吗？')
    await deviceAuthPlanDelete({ id })
    getLists()
}

const changeStatus = async (row: any) => {
    try {
        await deviceAuthPlanChangeStatus({
            id: row.id,
            status:
                row.status === DeviceAuthPlanStatus.ENABLED
                    ? DeviceAuthPlanStatus.DISABLED
                    : DeviceAuthPlanStatus.ENABLED
        })
    } finally {
        getLists()
    }
}

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: deviceAuthPlanLists,
    params: queryParams
})

getLists()
</script>
