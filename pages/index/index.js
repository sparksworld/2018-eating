//index.js
//获取应用实例
const app = getApp()
Page({
	data: {
		userInfo: {},
		props:{},
		pageData: {
			title: '2018，你靠什么吃饭？',
			imgBanner: {
				mode: "center",
				path: '../assets/images/banner.png'
			},
		}
	},
	//事件处理函数
	testTest: function () {
		wx.navigateTo({
			url: '../reault/reault?'
		})
	},
	getName(e) {
		if(e.detail.value) {
			this.setData({
				'props.val': e.detail.value,
				'props.avatar': this.data.userInfo.avatarUrl			
			})
		} else {
			this.setData({
				'props.val': this.data.userInfo.nickName,
				'props.avatar': this.data.userInfo.avatarUrl
			})
		}
		wx.setStorageSync('props', this.data.props)
	},
	onLoad: function () {
		var that = this
		//调用应用实例的方法获取全局数据
		app.getUserInfo(function (userInfo) {
			//更新数据
			that.setData({
				userInfo: userInfo
			})
		})
	}
})
