module.exports = {
<<<<<<< HEAD
    savePicToAlbum: function(tempFilePath) {
        let that = this;
        wx.getSetting({
            success(res) {
                if (!res.authSetting['scope.writePhotosAlbum']) {
                    wx.authorize({
                        scope: 'scope.writePhotosAlbum',
                        success() {
                            wx.saveImageToPhotosAlbum({
                                filePath: tempFilePath,
                                success(res) {
                                    wx.showToast({
                                        title: '保存成功'
                                    });
                                },
                                fail(res) {
                                    console.log(res);
                                }
                            })
                        },
                        fail() {
                            // 用户拒绝授权,打开设置页面
                            wx.openSetting({
                                success: function(data) {
                                    console.log("openSetting: success");
                                },
                                fail: function(data) {
                                    console.log("openSetting: fail");
                                }
                            });
                        }
                    })
                } else {
                    wx.saveImageToPhotosAlbum({
                        filePath: tempFilePath,
                        success(res) {
                            wx.showToast({
                                title: '保存成功',
                            });
                        },
                        fail(res) {
                            console.log(res);
                        }
                    })
                }
            },
            fail(res) {
                console.log(res);
            }
        })
    }
=======
	savePicToAlbum: function (tempFilePath) {
		let that = this;
		wx.getSetting({
			success(res) {
				if (!res.authSetting['scope.writePhotosAlbum']) {
					wx.authorize({
						scope: 'scope.writePhotosAlbum',
						success() {
							wx.saveImageToPhotosAlbum({
								filePath: tempFilePath,
								success(res) {
									wx.showToast({
										title: '保存成功'
									});
								},
								fail(res) {
									console.log(res);
								}
							})
						},
						fail() {
							// 用户拒绝授权,打开设置页面
							wx.openSetting({
								success: function (data) {
									console.log("openSetting: success");
								},
								fail: function (data) {
									console.log("openSetting: fail");
								}
							});
						}
					})
				} else {
					wx.saveImageToPhotosAlbum({
						filePath: tempFilePath,
						success(res) {
							wx.showToast({
								title: '保存成功',
							});
						},
						fail(res) {
							console.log(res);
						}
					})
				}
			},
			fail(res) {
				console.log(res);
			}
		})
	}
>>>>>>> master
}