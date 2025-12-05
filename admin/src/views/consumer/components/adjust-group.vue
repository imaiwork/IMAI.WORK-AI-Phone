<template>
	<Popup
		ref="popRef"
		width="auto"
		:title="`${type == 1 ? '调整' : '批量'}设置分组`"
		@confirm="submit">
		<el-form @submit.prevent>
			<el-form-item label="用户分组">
				<el-select v-model="group_ids" multiple class="w-[280px]">
					<el-option
						v-for="(item, index) in optionsData.dataList"
						:key="index"
						:label="item.name"
						:value="item.id" />
				</el-select>
			</el-form-item>
		</el-form>
	</Popup>
</template>

<script setup lang="ts">
import { userGroupingList, userGroupingset } from "@/api/consumer";
import { useDictOptions } from "@/hooks/useDictOptions";

const emits = defineEmits(["close"]);

const popRef = shallowRef();
//设置类型 1-单独 2-批量
const type = ref<number>(1);
//用户id
const id = ref<number[]>([]);
const group_ids = ref<number[]>([]);
const { optionsData, refresh } = useDictOptions<{ dataList: any }>({
	dataList: {
		api: userGroupingList,
	},
});

const open = (option: { id: number[]; groupIds: []; type: number }) => {
	popRef.value.open();
	id.value = option.id;
	group_ids.value = option.groupIds;
	type.value = option.type;
};

const submit = async () => {
	await userGroupingset({ ids: id.value, group_ids: group_ids.value });
	emits("close");
	popRef.value.close();
};

defineExpose({ open });
</script>

<style scoped lang="scss"></style>
