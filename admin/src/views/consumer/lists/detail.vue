<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-page-header content="用户详情" @back="$router.back()" />
        </el-card>
        <el-card class="mt-4 !border-none" header="基本资料" shadow="never">
            <el-form ref="formRef" class="ls-form" :model="formData" inline label-width="120px">
                <div class="bg-page flex py-2.5 mb-10 items-center flex-wrap">
                    <div class="basis-40 flex flex-1 flex-col py-[10px] justify-center items-center">
                        <div class="mb-2 text-tx-regular">用户头像</div>
                        <material-picker v-model="avatar" @change="changeAvatar" />
                    </div>
                    <div class="basis-20 flex flex-1 flex-col py-[10px] justify-center items-center">
                        <div class="text-tx-regular">算力值数量</div>
                        <div class="mt-2 flex items-center text-[20px]">
                            {{ formData.tokens || 0 }}
                            <el-button
                                v-perms="['user.user/adjustMoney']"
                                type="primary"
                                link
                                @click="handleAdjust('chat', formData.tokens)">
                                调整
                            </el-button>
                        </div>
                        <div>消耗算力：{{ formData.used_tokens.toFixed(2) || 0 }}</div>
                    </div>
                    <div class="basis-20 flex flex-1 flex-col py-[10px] justify-center items-center">
                        <div class="text-tx-regular">累计使用次数</div>
                        <div class="mt-2 flex items-center text-[20px]">
                            {{ formData.tokens_times || 0 }}
                        </div>
                    </div>
                    <div class="basis-20 flex flex-1 flex-col py-[10px] justify-center items-center">
                        <div class="text-tx-regular">累计充值金额</div>
                        <div class="mt-2 flex items-center text-[20px]">￥{{ formData.sum_price || 0 }}</div>
                    </div>
                    <div class="basis-20 flex flex-1 flex-col py-[10px] justify-center items-center">
                        <div class="text-tx-regular">下级累计充值金额</div>
                        <div class="mt-2 flex items-center text-[20px]">
                            ￥{{ formData.sub_recharge_amount || 0 }}
                            <el-button
                                v-perms="['user.user/resetRechargeStats']"
                                type="danger"
                                link
                                :loading="resetLoading"
                                @click="handleResetRechargeStats">
                                清零
                            </el-button>
                        </div>
                        <div class="text-xs text-tx-secondary">
                            {{ formData.sub_recharge_count || 0 }} 笔 · {{ formData.sub_user_count || 0 }} 位下级
                        </div>
                        <div class="text-xs text-tx-secondary">
                            历史累计：￥{{ formData.sub_recharge_history_amount || 0 }}（{{
                                formData.sub_recharge_history_count || 0
                            }}
                            笔，含已清零）
                        </div>
                        <div v-if="formData.recharge_stats_reset_time_text" class="text-xs text-tx-placeholder">
                            上次清零：{{ formData.recharge_stats_reset_time_text }}
                        </div>
                    </div>
                    <div class="basis-20 flex flex-1 flex-col py-[10px] justify-center items-center">
                        <div class="text-tx-regular">会员等级</div>
                        <div class="mt-2 flex items-center text-[20px]">
                            {{ formData.level_name || "默认" }}
                            <el-button type="primary" v-perms="['user.user/edit']" link @click="handleUserLevel">
                                调整
                            </el-button>
                        </div>
                    </div>
                    <div class="basis-20 flex flex-1 flex-col py-[10px] justify-center items-center">
                        <div class="text-tx-regular">代理等级</div>
                        <div class="mt-2 flex items-center text-[20px]">
                            {{ getGradeName }}
                            <el-button type="primary" v-perms="['user.user/edit']" link @click="handleAgentGrade">
                                调整
                            </el-button>
                        </div>
                    </div>
                </div>
                <el-form-item label="用户编号：">
                    {{ formData.sn }}
                </el-form-item>
                <el-form-item label="用户昵称：">
                    {{ formData.nickname || "-" }}
                    <popover-input
                        class="ml-[10px]"
                        @confirm="handleEdit($event, 'nickname')"
                        :limit="32"
                        v-perms="['user.user/edit']">
                        <el-button type="primary" link>
                            <icon name="el-icon-EditPen" />
                        </el-button>
                    </popover-input>
                </el-form-item>
                <el-form-item label="团队名称：">
                    {{ formData.team_name || "-" }}
                </el-form-item>
                <el-form-item label="账号：">
                    {{ formData.account }}
                </el-form-item>
                <el-form-item label="真实姓名：">
                    {{ formData.real_name || "-" }}
                    <popover-input
                        class="ml-[10px]"
                        @confirm="handleEdit($event, 'real_name')"
                        :limit="32"
                        v-perms="['user.user/edit']">
                        <el-button type="primary" link>
                            <icon name="el-icon-EditPen" />
                        </el-button>
                    </popover-input>
                </el-form-item>
                <el-form-item label="性别：">
                    {{ formData.sex }}
                    <popover-input
                        class="ml-[10px]"
                        type="select"
                        :options="[
                            { label: '未知', value: 0 },
                            { label: '男', value: 1 },
                            { label: '女', value: 2 },
                        ]"
                        @confirm="handleEdit($event, 'sex')"
                        v-perms="['user.user/edit']">
                        <el-button type="primary" link>
                            <icon name="el-icon-EditPen" />
                        </el-button>
                    </popover-input>
                </el-form-item>
                <el-form-item label="联系电话：">
                    {{ formData.mobile || "-" }}
                    <popover-input
                        class="ml-[10px]"
                        type="number"
                        @confirm="handleEdit($event, 'mobile')"
                        v-perms="['user.user/edit']">
                        <el-button type="primary" link>
                            <icon name="el-icon-EditPen" />
                        </el-button>
                    </popover-input>
                </el-form-item>
                <el-form-item label="注册来源：">
                    {{ formData.channel }}
                </el-form-item>
                <el-form-item label="多处登录：" v-if="false">
                    <span>{{ formData.multipoint_login == 1 ? "已开启" : "已关闭" }}</span>
                    <el-button link type="primary" @click="editMultipoint_login">{{
                        formData.multipoint_login == 1 ? "关闭" : "开启"
                    }}</el-button>
                </el-form-item>
                <el-form-item label="注册时间：">
                    {{ formData.create_time }}
                </el-form-item>
                <el-form-item label="最近登录时间：">
                    {{ formData.login_time || "-" }}
                </el-form-item>
                <el-form-item label="上级邀请人">
                    {{ formData.distribution_parent_name || "-" }}
                    <el-button type="primary" link @click="handleEditDistributionParent">
                        <icon name="el-icon-EditPen" />
                    </el-button>
                </el-form-item>
                <el-form-item label="代理资格：">
                    <span class="text-[#F2A626]">{{ formData.distribution_level > 0 ? "已开通" : "未开通" }}</span>
                    <router-link
                        v-if="formData.distribution_level > 0"
                        :to="{
                            path: getRoutePath('marketing.agent/detail'),
                            query: { id: formData.id },
                        }">
                        <el-button link type="primary"> 查看代理信息</el-button>
                    </router-link>
                </el-form-item>
                <el-form-item label="账号状态：">
                    <el-tag :type="formData.is_blacklist == 1 ? 'danger' : 'success'">
                        {{ formData.is_blacklist == 1 ? "已拉黑" : "正常" }}
                    </el-tag>
                </el-form-item>
            </el-form>
            <el-button
                v-if="formData.is_blacklist == 0"
                v-perms="['user.user/edit']"
                type="danger"
                @click="BlackList(1, formData.id, formData.nickname)">
                拉黑用户
            </el-button>
            <el-button
                v-if="formData.is_blacklist == 1"
                v-perms="['user.user/edit']"
                type="primary"
                @click="BlackList(2, formData.id, formData.nickname)">
                解除拉黑
            </el-button>
            <el-button v-perms="['user.user/rePassword']" @click="resetPassword"> 重置密码 </el-button>
        </el-card>
        <el-card class="mt-2 !border-none" shadow="never">
            <div>
                <el-tabs v-model="activeTab" @tab-change="handleTabChange">
                    <el-tab-pane label="订阅记录" name="subscribe" />
                    <el-tab-pane label="消耗记录" name="consume" />
                    <el-tab-pane label="手机设备" name="device" />
                    <el-tab-pane label="CDK记录" name="cdk" />
                </el-tabs>
            </div>

            <!-- 订阅记录表格 -->
            <template v-if="activeTab === 'subscribe'">
                <el-table :data="orderPager.lists" style="width: 100%" v-loading="orderPager.loading">
                    <el-table-column prop="sn" label="订单号" show-overflow-tooltip min-width="180" />
                    <el-table-column prop="package_name" label="套餐名称" show-overflow-tooltip min-width="140" />
                    <el-table-column prop="order_amount" label="付款价格" show-overflow-tooltip min-width="100" />
                    <el-table-column label="付款渠道" prop="pay_way" show-overflow-tooltip min-width="100" />
                    <el-table-column prop="package_tokens" label="算力数" show-overflow-tooltip />
                    <el-table-column prop="pay_time" label="购买时间" show-overflow-tooltip width="180" />
                    <el-table-column label="订单状态" min-width="100">
                        <template #default="{ row }">
                            <el-tag type="success" v-if="row.pay_status == 1">交易成功</el-tag>
                            <el-tag type="danger" v-if="row.pay_status == 0">交易失败</el-tag>
                        </template>
                    </el-table-column>
                </el-table>
                <div class="flex justify-end mt-4">
                    <pagination v-model="orderPager" @change="getOrderLists" />
                </div>
            </template>

            <!-- 消耗记录表格 -->
            <template v-if="activeTab === 'consume'">
                <el-table size="large" :data="consumePager.lists" v-loading="consumePager.loading">
                    <el-table-column label="ID" prop="id" min-width="80" />
                    <el-table-column label="当前算力" min-width="120">
                        <template #default="{ row }">
                            <div class="flex items-center gap-1 cursor-pointer">
                                <div>
                                    <span>{{ row.change_amount_desc }}</span
                                    >算力
                                </div>
                                <el-tooltip v-if="Object.keys(row.extra ?? {}).length > 0">
                                    <div class="leading-[0]">
                                        <Icon name="el-icon-Warning" />
                                    </div>
                                    <template #content>
                                        <div class="text-sm flex flex-col gap-1">
                                            <span v-for="(value, key) in row.extra" :key="key">
                                                {{ key }}:{{ value }}
                                            </span>
                                        </div>
                                    </template>
                                </el-tooltip>
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column label="备注" prop="remark" min-width="120" show-overflow-tooltip />
                    <el-table-column label="创建时间" prop="create_time" min-width="180" show-overflow-tooltip />
                </el-table>
                <div class="flex justify-end mt-4">
                    <pagination v-model="consumePager" @change="getConsumeLists" />
                </div>
            </template>

            <!-- 手机设备表格 -->
            <template v-if="activeTab === 'device'">
                <el-table
                    size="large"
                    :data="devicePager.lists"
                    v-loading="devicePager.loading"
                    style="width: 100%">
                    <el-table-column label="设备名称" min-width="140" show-overflow-tooltip>
                        <template #default="{ row }">{{ row.device_name || "-" }}</template>
                    </el-table-column>
                    <el-table-column label="设备号" prop="device_code" min-width="180" show-overflow-tooltip />
                    <el-table-column label="设备型号" min-width="140" show-overflow-tooltip>
                        <template #default="{ row }">{{ row.device_model || "-" }}</template>
                    </el-table-column>
                    <el-table-column label="剩余日期" min-width="140" show-overflow-tooltip>
                        <template #default="{ row }">
                            <el-tag :type="getDeviceAuthStatusTag(row)" size="small">
                                {{ getDeviceAuthStatusText(row) }}
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="创建时间" prop="create_time" min-width="180" />
                    <el-table-column label="操作" width="280" fixed="right">
                        <template #default="{ row }">
                            <el-button
                                v-if="canActivate(row)"
                                v-perms="['ai_application.device/redeem']"
                                type="primary"
                                link
                                @click="handleActivate(row)">
                                激活
                            </el-button>
                            <el-button
                                v-if="canRenew(row)"
                                v-perms="['ai_application.device/redeem']"
                                type="primary"
                                link
                                @click="handleActivate(row)">
                                续费
                            </el-button>
                            <el-button
                                v-perms="['ai_application.device/deviceTransfer']"
                                type="primary"
                                link
                                @click="handleTransferDevice(row)">
                                设备转移用户
                            </el-button>
                            <el-button
                                v-perms="['ai_application.device/delete']"
                                type="danger"
                                link
                                @click="handleRemoveDevice(row)">
                                移除
                            </el-button>
                        </template>
                    </el-table-column>
                    <template #empty>
                        <el-empty description="该用户暂无手机设备" />
                    </template>
                </el-table>
                <div class="flex justify-end mt-4">
                    <pagination v-model="devicePager" @change="getDeviceLists" />
                </div>
            </template>

            <!-- CDK记录表格 -->
            <template v-if="activeTab === 'cdk'">
                <el-table size="large" :data="cdkPager.lists" v-loading="cdkPager.loading" style="width: 100%">
                    <el-table-column label="CDK" prop="code" min-width="200" show-overflow-tooltip />
                    <el-table-column label="套餐" prop="type_desc" min-width="100" />
                    <el-table-column label="状态" min-width="100">
                        <template #default="{ row }">
                            <el-tag :type="row.status === DeviceAuthCodeStatus.USED ? 'info' : 'success'">
                                {{ row.status_desc }}
                            </el-tag>
                        </template>
                    </el-table-column>
                    <el-table-column label="使用设备" min-width="160" show-overflow-tooltip>
                        <template #default="{ row }">{{ row.device_code || "-" }}</template>
                    </el-table-column>
                    <el-table-column label="创建时间" prop="create_time" min-width="180" />
                    <el-table-column label="使用时间" min-width="180">
                        <template #default="{ row }">{{ row.use_time || "-" }}</template>
                    </el-table-column>
                    <template #empty>
                        <el-empty description="该用户暂无CDK" />
                    </template>
                </el-table>
                <div class="flex justify-end mt-4">
                    <pagination v-model="cdkPager" @change="getCdkLists" />
                </div>
            </template>
        </el-card>
        <account-adjust v-bind="adjustState" v-model:show="adjustState.show" @confirm="handleConfirmAdjust" />
        <reset-password-pop v-if="popShow" ref="resetPasswordRef" @close="popShow = false" />
        <vip-adjust v-if="showVip" ref="vipRef" @success="getDetails" @close="showVip = false"></vip-adjust>
        <leader-adjust
            v-if="showLeaderAdjust"
            ref="leaderAdjustRef"
            :user-info="formData"
            @success="getDetails"
            @close="showLeaderAdjust = false">
        </leader-adjust>
        <agent-parent-adjust
            v-if="showDistributionParent"
            ref="distributionParentRef"
            :user-info="formData"
            @success="getDetails"
            @close="showDistributionParent = false" />
        <user-level v-if="showUserLevel" ref="userLevelRef" @success="getDetails" @close="showUserLevel = false" />
        <activate-popup v-model="activateVisible" :device="currentDevice" @success="getDeviceLists" />
        <transfer-popup v-model="transferVisible" :device="currentDevice" @success="getDeviceLists" />
    </div>
</template>

<script lang="ts" setup name="consumerDetail">
import type { FormInstance } from "element-plus";
import { getAgentGradeConfig } from "@/api/marketing/agent";
import { accountLog } from "@/api/finance";
import {
    getUserDetail,
    userEdit,
    blackList,
    adjustTokens,
    getConsumeLists as getConsumeListsApi,
    resetRechargeStats,
} from "@/api/consumer";
import { getDeviceLists as getDeviceListsApi, deleteDevice } from "@/api/ai_application/device";
import { deviceAuthCodeLists } from "@/api/ai_application/device_auth";
import {
    DeviceAuthCodeStatus,
    getDeviceAuthStatusTag,
    getDeviceAuthStatusText,
    isDeviceActivated,
    isDeviceExpired,
    isDevicePermanent,
} from "@/enums/deviceAuthEnums";
import { isEmpty } from "@/utils/util";
import { getRoutePath } from "@/router";
import AccountAdjust from "../components/account-adjust.vue";
import feedback from "@/utils/feedback";
import resetPasswordPop from "../components/reset-password-pop.vue";
import VipAdjust from "../components/vip-adjust.vue";
import UserLevel from "../components/user-level.vue";
import LeaderAdjust from "../components/leader-adjust.vue";
import AgentParentAdjust from "../components/agent-parent-adjust.vue";
import ActivatePopup from "@/views/ai_application/device/lists/components/activate-popup.vue";
import TransferPopup from "@/views/ai_application/device/lists/components/transfer-popup.vue";
import { usePaging } from "@/hooks/usePaging";

const resetPasswordRef = shallowRef();
const popShow = ref(false);
const route = useRoute();

const formData = reactive({
    id: 0,
    avatar: "",
    channel: "",
    create_time: "",
    login_time: "",
    mobile: "",
    nickname: "",
    real_name: 0,
    company_name: "",
    team_name: "",
    sex: 0,
    sn: "",
    account: "",
    used_tokens: 0,
    tokens: 0,
    tokens_times: 0,
    sum_price: 0,
    sub_recharge_amount: 0,
    sub_recharge_count: 0,
    sub_user_count: 0,
    sub_recharge_history_amount: 0,
    sub_recharge_history_count: 0,
    recharge_stats_reset_time_text: "",
    level_id: 0,
    level_name: "",
    is_blacklist: 0,
    multipoint_login: 1,
    orders: [],
    distribution_level: 0,
    distribution_parent_name: "",
    is_member: false,
    member_start_time: "",
    member_end_time: "",
});

const avatar = ref("");
const showVip = ref<boolean>(false);
const vipRef = shallowRef();
const adjustState = reactive({
    show: false,
    value: 0,
    title: "",
    unit: "",
    type: "",
});

const showUserLevel = ref<boolean>(false);
const userLevelRef = shallowRef();

// ==================== Tab 切换逻辑 ====================
const activeTab = ref("subscribe");
const tabFetchMap: Record<string, () => void> = {
    subscribe: () => getOrderLists(),
    consume: () => getConsumeLists(),
    device: () => getDeviceLists(),
    cdk: () => getCdkLists(),
};
const handleTabChange = (tab: string) => {
    activeTab.value = tab;
    tabFetchMap[tab]?.();
};

// ==================== 订阅记录 ====================
const orderQueryParams = reactive({
    user_id: route.query.id,
});
const { pager: orderPager, getLists: getOrderLists } = usePaging({
    fetchFun: accountLog,
    params: orderQueryParams,
});

// ==================== 消耗记录 ====================
const consumeQueryParams = reactive({
    user_id: route.query.id,
    type: "tokens",
    action: 2,
});
const { pager: consumePager, getLists: getConsumeLists } = usePaging({
    fetchFun: getConsumeListsApi,
    params: consumeQueryParams,
});

// ==================== 手机设备 ====================
const deviceQueryParams = reactive({
    user_id: route.query.id,
});
const { pager: devicePager, getLists: getDeviceLists } = usePaging({
    fetchFun: getDeviceListsApi,
    params: deviceQueryParams,
});

const currentDevice = ref<Record<string, any> | null>(null);
const activateVisible = ref(false);
const transferVisible = ref(false);

const canActivate = (row: Record<string, any>) => !isDeviceActivated(row) && !isDeviceExpired(row);
const canRenew = (row: Record<string, any>) =>
    (isDeviceActivated(row) || isDeviceExpired(row)) && !isDevicePermanent(row);

const handleActivate = (row: Record<string, any>) => {
    currentDevice.value = row;
    activateVisible.value = true;
};

const handleTransferDevice = (row: Record<string, any>) => {
    currentDevice.value = row;
    transferVisible.value = true;
};

const handleRemoveDevice = async (row: Record<string, any>) => {
    await feedback.confirm("确定要移除该设备吗？");
    await deleteDevice({ id: row.id, device_code: row.device_code });
    getDeviceLists();
};

// ==================== CDK记录 ====================
const cdkQueryParams = reactive({
    user_id: route.query.id,
});
const { pager: cdkPager, getLists: getCdkLists } = usePaging({
    fetchFun: deviceAuthCodeLists,
    params: cdkQueryParams,
});

// ==================== 用户详情 ====================
const formRef = shallowRef<FormInstance>();
const gradeList = ref<any[]>([]);

const getGradeList = async () => {
    const res = await getAgentGradeConfig();
    gradeList.value = res;
};

const getGradeName = computed(() => {
    return gradeList.value.find((item) => item.level == formData.distribution_level)?.name || "普通用户";
});

const getDetails = async () => {
    const data = await getUserDetail({ id: route.query.id });
    Object.keys(formData).forEach((key) => {
        //@ts-ignore
        formData[key] = data[key];
    });
    avatar.value = data.avatar;
    tabFetchMap[activeTab.value]?.();
};

onBeforeUnmount(() => {
    activateVisible.value = false;
    transferVisible.value = false;
    currentDevice.value = null;
});

const changeAvatar = (value: string) => {
    avatar.value = value;
    handleEdit(value, "avatar");
};

const handleEdit = async (value: string | number, field: string) => {
    if (isEmpty(value)) return;
    await userEdit({ id: route.query.id, field, value });
    getDetails();
};

const editMultipoint_login = async () => {
    await userEdit({
        id: route.query.id,
        field: "multipoint_login",
        value: formData.multipoint_login == 1 ? 0 : 1,
    });
    getDetails();
};

// ==================== 算力调整 ====================
const handleAdjust = (type: "chat" | "duration", value: number) => {
    adjustState.show = true;
    adjustState.value = value;
    adjustState.type = type;
    switch (type) {
        case "chat":
            adjustState.title = "算力值";
            adjustState.unit = "算力值";
            break;
    }
};

const handleConfirmAdjust = async (value: any) => {
    switch (adjustState.type) {
        case "chat":
            await adjustTokens({ user_id: route.query.id, ...value });
            break;
    }
    adjustState.show = false;
    getDetails();
};

// ==================== 会员 / 用户类型 ====================
const handleAdjustVip = async () => {
    showVip.value = true;
    await nextTick();
    vipRef.value?.open("add");
    vipRef.value?.setFormData(formData, route.query.id);
};

const handleUserLevel = async () => {
    showUserLevel.value = true;
    await nextTick();
    userLevelRef.value?.open({
        id: route.query.id,
        level_id: formData.level_id,
        is_member: formData.is_member,
        member_start_time: formData.member_start_time,
        member_end_time: formData.member_end_time,
    });
};

// ==================== 黑名单 / 密码 ====================
const BlackList = async (type: number, id: number, nickname: string) => {
    await feedback.customConfirm(
        "是否将 ",
        ` ${type == 1 ? "拉黑" : "解除拉黑"}？拉黑后该用户将无法登录，请谨慎操作！`,
        nickname || "该用户",
        "color:red",
    );
    await blackList({ id, type });
    getDetails();
};

const resetPassword = async () => {
    popShow.value = true;
    await nextTick();
    resetPasswordRef.value.open(formData.id);
};

// ==================== 下级充值业绩清零 ====================
// 清零只重置该用户下级业绩的起算时间点，充值订单与算力/余额账单明细都不会被改动
const resetLoading = ref<boolean>(false);
const handleResetRechargeStats = async () => {
    if (resetLoading.value) return;
    await feedback.confirm(
        `确定将 ${formData.nickname || "该用户"} 的下级累计充值金额清零吗？\n` +
            "清零后该用户在后台和代理端看到的下级充值业绩、下级充值流水，都从本次操作时间重新累计。\n" +
            "被清零的金额仍会计入「历史累计」留档，随时可查。\n" +
            "每个下级自己的「累计充值金额」保持不变；充值订单、算力账单、余额账单均不会被删除或修改。",
        "下级充值业绩清零",
        { type: "warning", confirmButtonText: "确定清零", customClass: "whitespace-pre-line" },
    );

    resetLoading.value = true;
    try {
        const res: any = await resetRechargeStats({ id: route.query.id });
        feedback.msgSuccess(`已清零 ${res?.cleared_count ?? 0} 笔下级充值，共 ￥${res?.cleared_amount ?? 0}`);
        await getDetails();
    } finally {
        resetLoading.value = false;
    }
};

// ==================== 代理等级 / 上级邀请人 ====================
const showLeaderAdjust = ref<boolean>(false);
const leaderAdjustRef = shallowRef();
const handleAgentGrade = async () => {
    showLeaderAdjust.value = true;
    await nextTick();
    leaderAdjustRef.value?.open(formData);
};

const showDistributionParent = ref<boolean>(false);
const distributionParentRef = shallowRef();
const handleEditDistributionParent = async () => {
    showDistributionParent.value = true;
    await nextTick();
    distributionParentRef.value?.open(formData.id);
};

// ==================== 初始化 ====================
getGradeList();
getDetails();
</script>

<style lang="scss" scoped>
:deep() {
    .material-select {
        @apply rounded-full overflow-hidden;
    }
}
</style>
