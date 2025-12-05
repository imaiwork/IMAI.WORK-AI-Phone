export const materialsKeys = Object.values(
	import.meta.glob(["./material/*.ts", "!./material/_*.ts"], {
		eager: true,
	})
).map((module: any) => module?.default || module?.meta);
