<template>
    <popup
        ref="popupRef"
        title="团队设置"
        width="780px"
        :confirm-button-text="activeTab === 'members' ? '' : '保存'"
        :confirm-loading="isLock"
        async
        @confirm="lockFn"
        @close="close">
        <el-tabs v-model="activeTab">
            <!-- 品牌管理 -->
            <el-tab-pane label="品牌管理" name="brand">
                <el-form :model="tenant" label-width="100px" class="mt-2">
                    <el-form-item label="站点域名">
                        <el-input v-model="tenant.domain" placeholder="请输入站点域名" />
                        <div class="form-tips">不要带上http或者https，例如：www.baidu.com</div>
                    </el-form-item>
                    <el-form-item label="站点标题">
                        <el-input v-model="tenant.brand.name" placeholder="请输入站点标题" maxlength="30" />
                    </el-form-item>
                    <el-form-item label="站点icon">
                        <material-picker v-model="tenant.brand.web_logo" :limit="1" />
                        <div class="form-tips">建议尺寸：64*64像素，支持jpg，jpeg，png格式</div>
                    </el-form-item>
                    <el-form-item label="站点logo">
                        <material-picker v-model="tenant.brand.pc_logo" :limit="1" />
                        <div class="form-tips">建议尺寸：128*128像素，支持jpg，jpeg，png格式</div>
                    </el-form-item>
                    <el-form-item label="备案号">
                        <el-input
                            v-model="tenant.brand.icp_number"
                            maxlength="64"
                            clearable
                            placeholder="例如 京ICP备xxxxxxxx号-x" />
                        <div class="form-tips">展示在 PC 侧栏底部与小程序「我的」页脚，不填则不展示</div>
                    </el-form-item>
                    <el-form-item label="企业名称">
                        <el-input
                            v-model="tenant.brand.company_name"
                            maxlength="64"
                            clearable
                            placeholder="例如 xx科技有限公司" />
                        <div class="form-tips">展示在备案号下方，不填则不展示</div>
                    </el-form-item>
                </el-form>
            </el-tab-pane>

            <!-- 站点小程序 -->
            <el-tab-pane label="站点小程序" name="mnp">
                <el-form :model="tenant" label-width="100px" class="mt-2">
                    <el-form-item label="小程序名称">
                        <el-input v-model="tenant.mnp.name" placeholder="请输入小程序名称" />
                    </el-form-item>
                    <el-form-item label="原始ID">
                        <el-input v-model="tenant.mnp.original_id" placeholder="请输入小程序原始ID" />
                    </el-form-item>
                    <el-form-item label="AppID">
                        <el-input v-model="tenant.mnp.app_id" placeholder="请输入小程序AppID" />
                    </el-form-item>
                    <el-form-item label="AppSecret">
                        <el-input
                            v-model="tenant.mnp.app_secret"
                            type="password"
                            show-password
                            :placeholder="
                                tenant.mnp.has_app_secret
                                    ? '已配置，留空则保持不变；如需更换请重新填写'
                                    : '请输入小程序AppSecret'
                            " />
                        <div
                            v-if="tenant.mnp.has_app_secret && !tenant.mnp.app_secret"
                            class="form-tips text-success">
                            ✓ AppSecret 已配置
                        </div>
                    </el-form-item>
                    <el-form-item label="上传私钥">
                        <el-input
                            v-model="tenant.mnp.private_key"
                            type="textarea"
                            :rows="4"
                            :placeholder="
                                tenant.mnp.has_private_key
                                    ? '已配置，留空则保持不变；如需更换请粘贴新私钥'
                                    : '请粘贴小程序私钥内容'
                            " />
                        <div
                            v-if="tenant.mnp.has_private_key && !tenant.mnp.private_key"
                            class="form-tips text-success">
                            ✓ 私钥已配置
                        </div>
                    </el-form-item>
                    <el-form-item label="小程序二维码">
                        <image-contain
                            v-if="tenant.mnp.qr_code"
                            :src="tenant.mnp.qr_code"
                            width="100"
                            height="100" />
                        <span v-else class="text-gray-400">暂无二维码</span>
                    </el-form-item>
                </el-form>
            </el-tab-pane>

            <!-- 成员管理 -->
            <el-tab-pane label="成员管理" name="members">
                <el-table :data="members" size="large" v-loading="membersLoading" max-height="440">
                    <el-table-column label="成员" min-width="180">
                        <template #default="{ row }">
                            <div class="flex items-center">
                                <image-contain
                                    :src="row.avatar"
                                    width="32"
                                    height="32"
                                    radius="50%"
                                    fit="cover" />
                                <span class="ml-2">{{ row.nickname }}</span>
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column label="手机号" prop="mobile" min-width="130" />
                    <el-table-column label="角色" min-width="100">
                        <template #default="{ row }">{{ row.role_desc || '-' }}</template>
                    </el-table-column>
                    <el-table-column label="算力" prop="tokens" min-width="100" />
                    <el-table-column label="到期时间" min-width="170">
                        <template #default="{ row }">
                            <span :class="{ 'text-danger': row.expired }">
                                {{ row.team_expire_time_desc || row.team_expire_time || '永久' }}
                            </span>
                        </template>
                    </el-table-column>
                    <template #empty>
                        <el-empty description="暂无成员数据" />
                    </template>
                </el-table>
            </el-tab-pane>
        </el-tabs>
    </popup>
</template>

<script setup lang="ts">
import { teamTenant, teamSetTenant, teamMembers } from '@/api/team'
import { useLockFn } from '@/hooks/useLockFn'
import feedback from '@/utils/feedback'

const emit = defineEmits<{
    (e: 'close'): void
    (e: 'success'): void
}>()

const popupRef = ref()
const activeTab = ref('brand')
const teamId = ref<any>('')

const tenant = reactive<any>({
    team_id: '',
    domain: '',
    brand: {
        name: '',
        web_logo: '',
        pc_logo: '',
        icp_number: '',
        company_name: '',
    },
    mnp: {
        app_id: '',
        app_secret: '',
        original_id: '',
        name: '',
        qr_code: '',
        has_app_secret: false,
        has_private_key: false,
        private_key: '',
    },
})

const members = ref<any[]>([])
const membersLoading = ref(false)

const getTenant = async () => {
    const res = await teamTenant({ id: teamId.value })
    tenant.team_id = res.team_id
    tenant.domain = res.domain
    tenant.brand.name = res.brand?.name || ''
    tenant.brand.web_logo = res.brand?.web_logo || ''
    tenant.brand.pc_logo = res.brand?.pc_logo || ''
    tenant.brand.icp_number = res.brand?.icp_number || ''
    tenant.brand.company_name = res.brand?.company_name || ''
    tenant.mnp.app_id = res.mnp?.app_id || ''
    tenant.mnp.original_id = res.mnp?.original_id || ''
    tenant.mnp.name = res.mnp?.name || ''
    tenant.mnp.qr_code = res.mnp?.qr_code || ''
    // 接口脱敏：只回 has_*，明文不回传
    tenant.mnp.has_app_secret = !!Number(res.mnp?.has_app_secret)
    tenant.mnp.has_private_key = !!Number(res.mnp?.has_private_key)
    tenant.mnp.app_secret = ''
    tenant.mnp.private_key = ''
}

const getMembers = async () => {
    membersLoading.value = true
    try {
        members.value = await teamMembers({ id: teamId.value })
    } finally {
        membersLoading.value = false
    }
}

const submit = async () => {
    const mnp: any = {
        app_id: tenant.mnp.app_id,
        original_id: tenant.mnp.original_id,
        name: tenant.mnp.name,
        qr_code: tenant.mnp.qr_code,
    }
    // 空值不传，避免覆盖已配置的密钥/私钥
    if (String(tenant.mnp.app_secret || '').trim()) {
        mnp.app_secret = String(tenant.mnp.app_secret).trim()
    }
    if (String(tenant.mnp.private_key || '').trim()) {
        mnp.private_key = String(tenant.mnp.private_key).trim()
    }
    await teamSetTenant({
        id: teamId.value,
        domain: tenant.domain,
        brand: {
            name: tenant.brand.name,
            web_logo: tenant.brand.web_logo,
            pc_logo: tenant.brand.pc_logo,
            icp_number: String(tenant.brand.icp_number || '').trim(),
            company_name: String(tenant.brand.company_name || '').trim(),
        },
        mnp,
    })
    feedback.msgSuccess('保存成功')
    // 重新拉取以刷新密钥/私钥配置状态
    await getTenant()
    emit('success')
}

const { lockFn, isLock } = useLockFn(submit)

const open = async (id: any) => {
    teamId.value = id
    activeTab.value = 'brand'
    popupRef.value?.open()
    await getTenant()
    await getMembers()
}

const close = () => {
    emit('close')
}

defineExpose({
    open,
})
</script>

<style scoped lang="scss">
.text-danger {
    color: var(--el-color-danger);
}
.text-success {
    color: var(--el-color-success);
}
</style>
