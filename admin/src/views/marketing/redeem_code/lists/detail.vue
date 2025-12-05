<template>
    <div class="edit-popup">
        <popup ref="popupRef" title="使用详情" :async="true" width="920px" @confirm="handleSubmit" @close="handleClose">
            <div class="flex flex-wrap">
                <el-form-item label-width="90px" label="批次编号:">
                    {{ usageDetails.sn }}
                </el-form-item>
                <el-form-item label-width="90px" label="卡密面额:">
                    {{ usageDetails.content }}
                </el-form-item>
                <el-form-item label-width="90px" label="生效时间:">
                    {{ usageDetails.valid_time_desc }}
                </el-form-item>
                <el-form-item label-width="90px" label="生成数量:">
                    {{ usageDetails.card_num }}
                </el-form-item>
                <el-form-item label-width="90px" label="已使用:">
                    {{ usageDetails.use_num }}
                </el-form-item>
                <el-form-item label-width="120px" label="未使用:">
                    {{ usageDetails.unused_num }}
                </el-form-item>
            </div>
            <div>
                <el-form ref="formRef" class="mt-4" :model="queryParams" :inline="true">
                    <el-form-item label="使用状态">
                        <el-select v-model="queryParams.status" class="!w-[120px]">
                            <el-option label="全部" value />
                            <el-option label="未使用" :value="0" />
                            <el-option label="已使用" :value="1" />
                        </el-select>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="resetPage">查询</el-button>
                        <el-button
                            @click="
                                () => {
                                    queryParams.status = '';
                                    getLists();
                                }
                            "
                            >重置</el-button
                        >
                        <export-data class="ml-[12px]" :params="queryParams" :fetchFun="cardcodeRecordLists">
                            <el-button> 导出 </el-button>
                        </export-data>
                    </el-form-item>
                </el-form>
                <div>
                    <el-table size="large" height="400px" v-loading="pager.loading" :data="pager.lists">
                        <el-table-column label="ID" prop="id" min-width="60" />
                        <el-table-column label="卡密编号" prop="record_sn" min-width="140" />
                        <el-table-column label="状态" min-width="120">
                            <template #default="{ row }">
                                <el-tag type="success" v-if="row.status">已使用</el-tag>
                                <el-tag type="warning" v-else>未使用</el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="使用人" prop="nickname" min-width="140" />
                        <el-table-column label="使用时间" prop="use_time" min-width="150" />
                    </el-table>
                    <div class="flex justify-end mt-4">
                        <pagination v-model="pager" @change="getLists" />
                    </div>
                </div>
            </div>
        </popup>
    </div>
</template>
<script lang="ts" setup>
import type { FormInstance } from "element-plus";
import Popup from "@/components/popup/index.vue";
import { cardcodeDetails, cardcodeRecordLists } from "@/api/marketing/redeem_code";
import { usePaging } from "@/hooks/usePaging";

const emit = defineEmits(["success", "close"]);
//表单ref
const formRef = shallowRef<FormInstance>();
//弹框ref
const popupRef = shallowRef<InstanceType<typeof Popup>>();
//使用详情
const usageDetails = ref<any>({
    id: "",
    sn: "",
    type: 3,
    card_num: "",
    valid_start_time: "",
    valid_end_time: "",
    create_time: "",
    remark: "",
    type_desc: "",
    content: "",
    valid_time_desc: "",
    use_num: 0,
    unused_num: 0,
});

//表单数据
const queryParams = reactive<any>({
    id: "",
    status: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
    fetchFun: cardcodeRecordLists,
    params: queryParams,
});

//获取使用详情
const getUsageDetails = async (id: number) => {
    try {
        const data = await cardcodeDetails({ id });
        usageDetails.value = data;
    } catch (error) {
        console.log("获取使用详情=>", error);
    }
};

//提交表单
const handleSubmit = async () => {
    try {
        popupRef.value?.close();
        emit("success");
    } catch (error) {
        return error;
    }
};

const handleClose = () => {
    emit("close");
};

const open = (id: number) => {
    queryParams.id = id;
    popupRef.value?.open();
    getUsageDetails(id);
    getLists();
};

defineExpose({ open });
</script>
