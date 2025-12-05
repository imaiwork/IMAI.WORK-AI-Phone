<template>
	<div>
		<div class="fixed right-4 bottom-8">
			<div class="flex flex-col gap-2">
				<ElTooltip
					placement="left"
					content="新增思维导图"
					v-if="showAdd">
					<div>
						<ElButton
							type="primary"
							class="!w-[36px] !h-[36px] !p-0"
							@click="emit('add')">
							<template #icon>
								<Icon name="el-icon-Plus" size="20"></Icon>
							</template>
						</ElButton>
					</div>
				</ElTooltip>
				<ElTooltip placement="left" content="刷新" v-if="showRefresh">
					<div>
						<ElButton
							@click="emit('refresh')"
							type="primary"
							class="!w-[36px] !h-[36px] !p-0">
							<Icon name="el-icon-Refresh" :size="20"></Icon>
						</ElButton>
					</div>
				</ElTooltip>
				<ElTooltip placement="left" content="返回" v-if="showBack">
					<div>
						<ElButton
							@click="emit('back')"
							type="primary"
							class="!w-[36px] !h-[36px] !p-0">
							<Icon name="el-icon-Back" :size="20"></Icon>
						</ElButton>
					</div>
				</ElTooltip>
			</div>
		</div>
	</div>
</template>

<script setup lang="ts">
const props = withDefaults(
	defineProps<{
		showBack?: boolean;
		showRefresh?: boolean;
		showAdd?: boolean;
	}>(),
	{
		showBack: true,
		showRefresh: true,
		showAdd: true,
	}
);

const emit = defineEmits<{
	(e: "back"): void;
	(e: "refresh"): void;
	(e: "add"): void;
}>();
</script>

<style scoped></style>
