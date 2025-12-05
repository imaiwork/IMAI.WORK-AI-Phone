export enum TurnStatus {
	WAITING = 0, // 待处理
	RECORDING = 1, // 录音中
	RECORDING_PAUSE = 2, // 录音暂停
	ING = 3, // 转写中
	SUCCESS = 4, // 转写成功
	ERROR = 5, // 转写失败
}
