<template>
    <div>
        <el-card shadow="never" class="!border-none mt-[10px]">
            <el-form ref="formRef" class="mt-4" :model="queryParams" :inline="true">
                <el-form-item label="批次编号">
                    <el-input class="w-[280px]" v-model="queryParams.sn" placeholder="请输入批次编号" clearable />
                </el-form-item>

                <el-form-item label="生效时间">
                    <daterange-picker
                        class="w-[280px]"
                        v-model:startTime="queryParams.start_time"
                        v-model:endTime="queryParams.end_time" />
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="resetPage">查询</el-button>
                    <el-button @click="resetParams">重置</el-button>
                    <export-data
                        class="ml-2.5"
                        :fetch-fun="cardcodeLists"
                        :params="queryParams"
                        :page-size="pager.size" />
                </el-form-item>
            </el-form>
        </el-card>
        <el-card shadow="never" class="!border-none mt-[10px]">
            <div>
                <el-button v-perms="['cardcode.cardCode/add']" type="primary" @click="handleRedeemCodeAdd()">
                    <template #icon>
                        <icon name="el-icon-Plus" />
                    </template>
                    新增
                </el-button>
            </div>
            <el-table size="large" class="mt-4" v-loading="pager.loading" :data="pager.lists">
                <el-table-column label="批次编号" prop="sn" min-width="120" />
                <el-table-column label="卡密内容" prop="content" min-width="100" />
                <el-table-column label="已使用/数量" prop="num_use_desc" min-width="100" />
                <el-table-column label="生效时间" prop="sort" min-width="220">
                    <template #default="{ row }">
                        <div>生效时间：{{ row.valid_start_time_desc }}</div>
                        <div>失效时间：{{ row.valid_end_time_desc }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="创建时间" prop="create_time" sortable min-width="180" />
                <el-table-column label="备注" prop="remark" min-width="100" />
                <el-table-column label="操作" width="150" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link @click="handleUsageDetails(row.id)"> 使用详情 </el-button>
                        <el-button
                            v-perms="['cardcode.cardCode/delete']"
                            type="danger"
                            link
                            @click="handleDelete(row.id)">
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
        <details-popup v-if="showDetails" ref="detailsRef" @success="getLists" @close="showDetails = false" />
    </div>
</template>
<script lang="ts" setup name="redeemCodeList">
import { usePaging } from "@/hooks/usePaging";
import { cardcodeLists, cardcodeDel } from "@/api/marketing/redeem_code";
import feedback from "@/utils/feedback";

import EditPopup from "./edit.vue";
import DetailsPopup from "./detail.vue";

const queryParams = reactive({
    type: "",
    sn: "",
    start_time: "",
    end_time: "",
});

//弹框ref
const showEdit = ref<boolean>(false);
const editRef = shallowRef<InstanceType<typeof EditPopup>>();
//弹框ref
const showDetails = ref<boolean>(true);
const detailsRef = shallowRef<InstanceType<typeof DetailsPopup>>();

//打开弹框
const handleRedeemCodeAdd = async () => {
    showEdit.value = true;
    await nextTick();
    editRef.value?.open();
};

const handleUsageDetails = async (id: number) => {
    showDetails.value = true;
    await nextTick();
    detailsRef.value?.open(id);
};

//删除
const handleDelete = async (id: number) => {
    await feedback.confirm("确定要删除？");
    await cardcodeDel({ id });
    getLists();
};

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: cardcodeLists,
    params: queryParams,
});

getLists();
</script>
