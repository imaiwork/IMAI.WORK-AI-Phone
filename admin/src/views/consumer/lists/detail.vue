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
                    </div>
                    <div class="basis-20 flex flex-1 flex-col py-[10px] justify-center items-center">
                        <div class="text-tx-regular">累计使用次数</div>
                        <div class="mt-2 flex items-center text-[20px]">
                            {{ formData.tokens_times || 0 }}
                        </div>
                    </div>
                    <div class="basis-20 flex flex-1 flex-col py-[10px] justify-center items-center">
                        <div class="text-tx-regular">累计充值金额</div>
                        <div class="mt-2 flex items-center text-[20px]">￥{{ formData.sun_price || 0 }}</div>
                    </div>
                    <div class="basis-20 flex flex-1 flex-col py-[10px] justify-center items-center">
                        <div class="text-tx-regular">会员类型</div>
                        <div class="mt-2 flex items-center text-[20px]">
                            {{ formData.user_type == 0 ? "个人" : "企业" }}
                            <el-button type="primary" v-perms="['user.user/edit']" link @click="handleUserType">
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
                <el-form-item label="企业名称：">
                    {{ formData.company_name || "-" }}
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
                            {
                                label: '未知',
                                value: 0,
                            },
                            {
                                label: '男',
                                value: 1,
                            },
                            {
                                label: '女',
                                value: 2,
                            },
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
            </el-form>
            <el-button
                v-if="formData.is_blacklist == 0"
                v-perms="['user.user/blacklist']"
                @click="BlackList(1, formData.id, formData.nickname)"
                >加入黑名单</el-button
            >
            <el-button v-if="formData.is_blacklist == 1" @click="BlackList(2, formData.id, formData.nickname)"
                >移出黑名单</el-button
            >
            <el-button v-perms="['user.user/rePassword']" @click="resetPassword"> 重置密码 </el-button>
        </el-card>
        <el-card class="mt-2 !border-none" header="订阅记录" shadow="never">
            <el-table :data="orderPager.lists" style="width: 100%" v-loading="orderPager.loading">
                <el-table-column prop="sn" label="订单号" show-overflow-tooltip min-width="180" />
                <el-table-column prop="package_name" label="套餐名称" show-overflow-tooltip min-width="140" />
                <el-table-column prop="order_amount" label="付款价格" show-overflow-tooltip min-width="100" />
                <el-table-column label="付款渠道" prop="pay_way" show-overflow-tooltip min-width="100">
                </el-table-column>
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
        </el-card>
        <account-adjust v-bind="adjustState" v-model:show="adjustState.show" @confirm="handleConfirmAdjust" />
        <reset-password-pop v-if="popShow" ref="resetPasswordRef" @close="popShow = false" />
        <VipAdjust v-if="showVip" ref="vipRef" @success="getDetails" @close="showVip = false"></VipAdjust>
        <UserType
            v-if="showUserType"
            v-model:user_type="userTypeState.user_type"
            ref="userTypeRef"
            @success="changeUserType"
            @close="showUserType = false"></UserType>
    </div>
</template>

<script lang="ts" setup name="consumerDetail">
import type { FormInstance } from "element-plus";
import { accountLog } from "@/api/finance";
import { getUserDetail, userEdit, blackList, adjustTokens } from "@/api/consumer";
import { isEmpty } from "@/utils/util";
import AccountAdjust from "../components/account-adjust.vue";
import feedback from "@/utils/feedback";
import resetPasswordPop from "../components/reset-password-pop.vue";
import VipAdjust from "../components/vip-adjust.vue";
import UserType from "../components/user-type.vue";
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
    sex: 0,
    sn: "",
    account: "",
    tokens: 0,
    tokens_times: 0,
    sun_price: 0,
    user_type: 0,
    is_blacklist: 0,
    multipoint_login: 1,
    orders: [],
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

const showUserType = ref<boolean>(false);
const userTypeRef = shallowRef();
const userTypeState = reactive({
    user_type: 0,
});

const handleUserType = async () => {
    showUserType.value = true;
    await nextTick();
    userTypeRef.value?.open({
        userType: formData.user_type,
    });
};

const changeUserType = (userType: number) => {
    handleEdit(userType, "user_type");
    showUserType.value = false;
};

const adjustNumState = reactive({
    type: "",
    show: false,
    value: "",
    use: "",
});

const formRef = shallowRef<FormInstance>();

// 获取订单记录
const orderQueryParams = reactive({
    user_id: route.query.id,
});
const {
    pager: orderPager,
    getLists: getOrderLists,
    resetPage,
    resetParams,
} = usePaging({
    fetchFun: accountLog,
    params: orderQueryParams,
});

const getDetails = async () => {
    const data = await getUserDetail({
        id: route.query.id,
    });
    Object.keys(formData).forEach((key) => {
        //@ts-ignore
        formData[key] = data[key];
    });
    avatar.value = data.avatar;
    getOrderLists();
};

const changeAvatar = (value: string) => {
    avatar.value = value;
    handleEdit(value, "avatar");
};

const handleEdit = async (value: string | number, field: string) => {
    if (isEmpty(value)) return;
    await userEdit({
        id: route.query.id,
        field,
        value,
    });
    getDetails();
};

//编辑多点登录
const editMultipoint_login = async () => {
    await userEdit({
        id: route.query.id,
        field: "multipoint_login",
        value: formData.multipoint_login == 1 ? 0 : 1,
    });
    getDetails();
};

//余额调整
const handleAdjust = (type: "chat" | "duration", value: number) => {
    adjustState.show = true;
    adjustState.value = value;
    adjustState.type = type;
    switch (type) {
        case "chat":
            {
                adjustState.title = "算力值";
                adjustState.unit = "算力值";
            }
            break;
    }
};

//余额调整提交
const handleConfirmAdjust = async (value: any) => {
    switch (adjustState.type) {
        case "chat":
            await adjustTokens({
                user_id: route.query.id,
                ...value,
            });
            break;
    }

    adjustState.show = false;
    getDetails();
};

const handleAdjustVip = async () => {
    showVip.value = true;
    await nextTick();
    vipRef.value?.open("add");
    vipRef.value?.setFormData(formData, route.query.id);
};

//黑名单
const BlackList = async (type: number, id: number, nickname: string) => {
    await feedback.customConfirm(
        "是否将 ",
        ` ${type == 1 ? "加入" : "移出"}黑名单？请谨慎操作！`,
        nickname,
        "color:red"
    );
    await blackList({ id });
    getDetails();
};

//重置密码
const resetPassword = async () => {
    popShow.value = true;
    await nextTick();
    resetPasswordRef.value.open(formData.id);
};

getDetails();
</script>

<style lang="scss" scoped>
:deep() {
    .material-select {
        @apply rounded-full overflow-hidden;
    }
}
</style>
