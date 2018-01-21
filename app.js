//app.js
App({
	globalData: {
		userInfo: null
	},
	getUserInfo(cb) {
		var that = this
		if (this.globalData.userInfo) {
			typeof cb == "function" && cb(this.globalData.userInfo)
		} else {
			//调用登录接口
			wx.getUserInfo({
				success: function (res) {
					// console.log(res)
					that.globalData.userInfo = res.userInfo
					typeof cb == "function" && cb(that.globalData.userInfo)
				},
				fail: function () {
					wx.showModal({
						title: '提示',
						content: '亲，您还未授权，将不能获得更好的体验，请允许授权',
						showCancel: false,
						success: function (res) {
							wx.openSetting({
								success: function () {
									wx.getUserInfo({
										success: res => {
											that.globalData.userInfo = res.userInfo													
											typeof cb == "function" && cb(that.globalData.userInfo)
										}
									})
								}
							})
						}
					})
				}
			})
		}
	}
})