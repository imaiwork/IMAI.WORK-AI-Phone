<template>
    <div>
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" class="mb-[-16px]" :model="queryParams" :inline="true">
                <el-form-item label="用户信息">
                    <el-input
                        class="w-[280px]"
                        v-model="queryParams.keyword"
                        placeholder="用户编号/昵称/手机号码"
                        clearable
                        @keyup.enter="resetPage" />
                </el-form-item>
                <el-form-item label="注册时间">
                    <daterange-picker
                        v-model:startTime="queryParams.create_time_start"
                        v-model:endTime="queryParams.create_time_end" />
                </el-form-item>
                <el-form-item label="注册来源">
                    <el-select class="!w-[180px]" v-model="queryParams.channel">
                        <el-option v-for="(item, key) in ClientMap" :key="key" :label="item" :value="key" />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                    <!-- <export-data
                        class="ml-2.5"
                        :fetch-fun="getUserList"
                        :params="queryParams"
                        :page-size="pager.size" /> -->
                </el-form-item>
            </el-form>
        </el-card>
        <el-card class="!border-none mt-4" shadow="never">
            <div class="flex justify-between">
                <el-button v-perms="['user.user/add']" type="primary" @click="addUser">+ 创建用户</el-button>
                <el-button v-perms="['user.user/add']" type="primary" @click="batchImport">批量导入</el-button>
            </div>
            <el-table class="mt-4" size="large" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="用户编号" prop="sn" min-width="120" show-overflow-tooltip />
                <el-table-column label="头像" min-width="100">
                    <template #default="{ row }">
                        <el-avatar :src="row.avatar" :size="50" />
                    </template>
                </el-table-column>
                <el-table-column label="昵称" prop="nickname" min-width="120" show-overflow-tooltip />
                <el-table-column label="手机号码" prop="mobile" min-width="100" show-overflow-tooltip />
                <el-table-column label="类型" min-width="100" show-overflow-tooltip>
                    <template #default="{ row }">
                        <el-tag size="small" :type="row.user_type == 0 ? 'success' : 'warning'">
                            {{ row.user_type == 0 ? "个人" : "企业" }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="余额" min-width="160" show-overflow-tooltip>
                    <template #default="{ row }">
                        <div>算力值数量：{{ row.tokens }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="会员" min-width="100" v-if="false">
                    <template #default="{ row }">
                        <div class="text-[#4073FA]">{{ row.package_name }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="到期时间" prop="package_time" min-width="180" v-if="false">
                    <template #default="{ row }" v-if="false">
                        <div>{{ row.package_time }}</div>
                        <!-- <div v-if="row.is_end" class="text-[#FBAE00]">已到期</div> -->
                    </template>
                </el-table-column>

                <el-table-column label="累计提问" prop="total_chat" min-width="100" v-if="false" />
                <el-table-column label="注册来源" prop="channel" min-width="100" show-overflow-tooltip />
                <el-table-column label="注册时间" prop="create_time" min-width="120" show-overflow-tooltip />
                <el-table-column label="操作" min-width="60" fixed="right">
                    <template #default="{ row }">
                        <div class="flex items-center">
                            <el-button v-perms="['user.user/detail']" type="primary" link>
                                <router-link
                                    :to="{
                                        path: getRoutePath('user.user/detail'),
                                        query: {
                                            id: row.id,
                                        },
                                    }">
                                    详情
                                </router-link>
                            </el-button>
                        </div>
                    </template>
                </el-table-column>
            </el-table>
            <div class="flex justify-end mt-4">
                <pagination v-model="pager" @change="getLists" />
            </div>
        </el-card>
        <add-user-pop v-if="popShow" ref="addUserPopRef" @close="popClose" />
        <import-user-pop v-if="importShow" ref="importUserPopRef" @close="importShow = false" @success="getLists()" />
    </div>
</template>
<script lang="ts" setup name="consumerLists">
import { usePaging } from "@/hooks/usePaging";
import { getRoutePath } from "@/router";
import { getUserList } from "@/api/consumer";
import { ClientMap } from "@/enums/appEnums";
import AddUserPop from "../components/add-user-pop.vue";
import ImportUserPop from "./import.vue";
//弹框ref
const addUserPopRef = shallowRef();
const popShow = ref(false);

const queryParams = reactive({
    keyword: "",
    channel: "",
    create_time_start: "",
    create_time_end: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: getUserList,
    params: queryParams,
});

//打开创建用户弹框
const addUser = async () => {
    popShow.value = true;
    await nextTick();
    addUserPopRef.value.open();
};

//弹框关闭
const popClose = () => {
    getLists();
    popShow.value = false;
};

// 批量导入
const importShow = ref(false);
const importUserPopRef = shallowRef();
const batchImport = async () => {
    importShow.value = true;
    await nextTick();
    importUserPopRef.value.open();
};

onActivated(() => {
    getLists();
});

getLists();
</script>
