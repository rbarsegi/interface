$(document).ready(function () {

    function plsRestartDaemon() {
        toastr.options = {
            "positionClass": "toast-top-right",
            "closeButton": false,
            "progressBar": true,
            "showEasing": "swing",
            "timeOut": "15000"
        };
        toastr.info("To confirm the changes please restart the daemon.");

        $("#restart_daemon_btn").addClass("buttonmarked");
    }

    var elem = document.querySelector('.staking-switch');
    var init = new Switchery(elem, {color: "#b6b6b6"});
    /*
     elem.onchange = function () {
     };
     */
    init.disable();

    $(".masternode-switch").prop('checked', false);
    var elem2 = document.querySelector('.masternode-switch');
    var init2 = new Switchery(elem2, {disabled: true, color: "#18ee30", secondaryColor: "#efefef"});
    elem2.onchange = function () {
        plsRestartDaemon();
        setMasternodeEnable($(".masternode-switch").is(':checked'));
    };


    var payoutOptions = {
        responsive: true,
        legend: {
            labels: {
                fontColor: "#90969D"
            },
            display: false
        },
        scales: {
            xAxes: [{
                    ticks: {
                        fontColor: "#90969D"
                    },
                    gridLines: {
                        color: "#37393F"
                    }
                }],
            yAxes: [{
                    ticks: {
                        fontColor: "#90969D",
                        beginAtZero: true,
                        min: 0,
                        stepSize: 5
                    },
                    gridLines: {
                        color: "#37393F"
                    }
                }]
        }
    };

    var barData = {
        labels: ["_", "_", "_", "_", "_", "_", "_"],
        datasets: [
            {
                label: "Payouts",
                backgroundColor: 'transparent',
                borderColor: "#18ee30",
                borderWidth: 1,
                data: [0, 0, 0, 0, 0, 0, 0]
            }
        ]
    };

    // var c3 = document.getElementById("barOptions").getContext("2d");
    // var chart = new Chart(c3, {type: 'bar', data: barData, options: payoutOptions});

    /*                                        .alqoswitch.enabled>span {
     background-color: #18ee30 !important;
     border-color: #18ee30 !important;
     box-shadow: 0px 0px 0px 16px #18ee30 inset !important;
     }
     .alqoswitch.disabled>span {
     background-color: rgb(255, 255, 255);
     border-color: rgb(223, 223, 223);
     box-shadow: 0px 0px 0px 0px rgb(223, 223, 223) inset;
     }

     */



    var chartUsersOptions_CPU = {
        lines: {
            show: true,
            fill: false,
            lineWidth: 2

        },
        yaxis: {
            min: 0, max: 100, tickSize: 25
        },
        grid: {
            tickColor: "#484C5A",
            borderWidth: 0
        },
        colors: ["#18ee30"]
    };
    var chartUsersOptions_RAM = {
        lines: {
            show: true,
            fill: false,
            lineWidth: 2
        },
        yaxis: {
            min: 0, max: 100, tickSize: 25
        },
        grid: {
            tickColor: "#484C5A",
            borderWidth: 0
        },
        colors: ["#18ee30"]
    };

    function updateCpuGraph(CpuData) {
        var res = [];
        for (var i = 0; i < CpuData.length; ++i) {
            res[i] = [i, CpuData[i]];
        }

        var plot_cpu = $.plot($("#metric_cpu"), [res], chartUsersOptions_CPU);
        plot_cpu.setData([res]);
        plot_cpu.draw();
    }

    function updateRamGraph(RamData) {
        var res = [];
        for (var i = 0; i < RamData.length; ++i) {
            res[i] = [i, RamData[i]];
        }

        var plot_cpu = $.plot($("#metric_ram"), [res], chartUsersOptions_RAM);
        plot_cpu.setData([res]);
        plot_cpu.draw();
    }

    function reverseString(inputstr){
        var newstring = "";
        for (var i = inputstr.length - 1; i >= 0; i--) {
            newstring += inputstr[i]; // or newString = newString + str[i];
        }
        return newstring;
    }

    function UpdateSysGenkey(){
        if ($("#sys_genkey").attr("data-show") == "true"){
            $("#sys_genkey").html(reverseString($("#sys_genkey").attr("data-key")));
        } else {
            var tmpString = "";
            for (var i = 0; i < $("#sys_genkey").attr("data-key").length ; i++){
                tmpString += "*";
            }
            $("#sys_genkey").html(tmpString);
        }
    }

    $("#showGenkey").click(function(event){
        event.preventDefault();
        if ($("#sys_genkey").attr("data-show") == "true"){
            $("#sys_genkey").attr("data-show","false");
            $("#showGenkey").html("show");
        }
        else{
            $("#sys_genkey").attr("data-show","true");
            $("#showGenkey").html("hide");
        }
        UpdateSysGenkey();

    });

    function UpdateKey() {
        var retval = $.get("ajax.php", {getPrivKey: ""}, function (data) {
            data = reverseString(data);
            $("#sys_genkey").attr("data-key",data);
            UpdateSysGenkey();
            setTimeout(UpdateKey, 1000);
        });
    }

    function triggerGenkeyDisplay(){
            $("#sys_genkey").html(data);
    }

    function UpdateMasternodeActivity() {
        $.get("ajax.php", {isMasternode: ""}, function (data) {
            var isEnabled = false;
            if (data == "1") {
                isEnabled = true;
            } else if (data == "0") {
                isEnabled = false;
            }

            if (isEnabled !== $(".masternode-switch").is(':checked')) {
                $(".masternode-switch").prop('checked', isEnabled);
                $(".masternode-switch").siblings().remove();
                var init2 = new Switchery(elem2, {disabled: true, color: "#18ee30", secondaryColor: "#efefef"});
                elem2.onchange = function () {
                    plsRestartDaemon();
                    setMasternodeEnable($(".masternode-switch").is(':checked'));
                };
            }

            setTimeout(UpdateMasternodeActivity, 1000);
        });
    }

    function UpdateStakingActivity() {
        $.get("ajax.php", {isStaking: ""}, function (data) {
            if (data == "1")
                $("#sys_staking").html("enabled");
            else if (data == "0")
                $("#sys_staking").html("disabled");
            else
                $("#sys_staking").html("unknown");
            setTimeout(UpdateStakingActivity, 5000);
        });
    }

    function setMasternodeEnable(enabled) {
        if (enabled)
            $.get("ajax.php", {setMasternode: "1"}, function (data) {
            });
        else
            $.get("ajax.php", {setMasternode: "0"}, function (data) {
            });
    }
    function setStakingEnable(enabled) {
        if (enabled)
            $.get("ajax.php", {setStaking: "1"}, function (data) {
            });
        else
            $.get("ajax.php", {setStaking: "0"}, function (data) {
            });
    }
    function restartDaemon() {
        toastr.options = {
            "positionClass": "toast-top-right",
            "closeButton": false,
            "progressBar": true,
            "showEasing": "swing",
            "timeOut": "20000"
        };
        toastr.warning("Daemon restarting intiated. Please wait.");
        $.get("ajax.php", {restartDaemon: ""}, function (data) {
            toastr.options = {
                "positionClass": "toast-top-right",
                "closeButton": false,
                "progressBar": true,
                "showEasing": "swing",
                "timeOut": "2000"
            };
            toastr.info("Success.");
            setTimeout(function () {
                location.reload();
            }, 2000);
        });
    }
    function reindexDaemon() {
        toastr.options = {
            "positionClass": "toast-top-right",
            "closeButton": false,
            "progressBar": true,
            "showEasing": "swing",
            "timeOut": "20000"
        };
        toastr.warning("Daemon reindex intiated. Please wait.");
        $.get("ajax.php", {reindexDaemon: ""}, function (data) {
            toastr.options = {
                "positionClass": "toast-top-right",
                "closeButton": false,
                "progressBar": true,
                "showEasing": "swing",
                "timeOut": "2000"
            };
            toastr.info("Success.");
            setTimeout(function () {
                location.reload();
            }, 2000);
        });
    }
    function resetServer() {
        toastr.options = {
            "positionClass": "toast-top-right",
            "closeButton": false,
            "progressBar": true,
            "showEasing": "swing",
            "timeOut": "20000"
        };
        toastr.warning("Server reset initiated. Please wait.");
        $.get("ajax.php", {resetServer: ""}, function (data) {
            toastr.options = {
                "positionClass": "toast-top-right",
                "closeButton": false,
                "progressBar": true,
                "showEasing": "swing",
                "timeOut": "2000"
            };
            toastr.info("Success." + data);
            setTimeout(function () {
                location.reload();
            }, 2000);
        });
    }
    function setPrivKey(key) {
        $.get("ajax.php", {setPrivKey: key}, function (data) {
            $.get("ajax.php", {getPrivKey: ""}, function (data) {
                plsRestartDaemon();
                $("#mnKeyInput").val(data);
                $("#sys_genkey").html(data);
            });
        });
    }

    function UpdateRAMandCPU() {
        $.get("ajax.php", {serverresources: ""}, function (data) {
            var tmpJSON = JSON.parse(data);
            strRamUsageMB = tmpJSON.RAMUSAGE;
            ramUsagePercentageArr = tmpJSON.RAMUSAGEPERCENTAGE;
            cpuUsagePercentageArr = tmpJSON.CPUUSAGE;

            $("#actRamUsage").html(strRamUsageMB + " MB (" + ramUsagePercentageArr[ramUsagePercentageArr.length - 1] + "%)");
            $("#actCpuUsage").html("(" + Math.min(Math.max(cpuUsagePercentageArr[cpuUsagePercentageArr.length - 1], 1), 100) + "%)");

            updateRamGraph(ramUsagePercentageArr);
            updateCpuGraph(cpuUsagePercentageArr);
            setTimeout(UpdateRAMandCPU, 1000);
        });
    }
    function UpdateINFO() {
        $.get("ajax.php", {info: ""}, function (data) {
            var tmpJSON = JSON.parse(data);
            strDaemonStatus = tmpJSON.status;
            strMasternodeStatus = tmpJSON.masternodeStatus;
            strDiffifulty = tmpJSON.difficulty;
            strBlocknumber = tmpJSON.block;
            strWalletVersion = tmpJSON.walletVersion;
            strProtocolVersion = tmpJSON.protocolVersion;
            strVersion = tmpJSON.version;
            strConnections = tmpJSON.connections;
            strMasternodeIP = tmpJSON.masternodeIp;
            strWalletBalance = tmpJSON.masternodeWalletBalance;

            masternodepayoutdata = tmpJSON.masternodePayoutData;
            strCompletePayouts = masternodepayoutdata.overall;
            lastPayouts = masternodepayoutdata.lastPayouts;
            payoutsWeek = masternodepayoutdata.payouts;

            $("#sys_balance").html(strWalletBalance);

            switch (strDaemonStatus) {
                case true:
                    $("#sys_daemonstatus").html("Online");
                    break;
                case false:
                    $("#sys_daemonstatus").html("Offline");
                    break;
                default:
                    $("#sys_daemonstatus").html("Unknown");
                    break;
            }

            switch (strMasternodeStatus) {
                case true:
                    $("#sys_masternode").html("Online");
                    break;
                case false:
                    $("#sys_masternode").html("Offline");
                    break;
                default:
                    $("#sys_masternode").html("Unknown");
                    break;
            }

            $("#sys_blocknumber").html(strBlocknumber);
            $("#sys_wversion").html(strWalletVersion);
            $("#sys_pversion").html(strProtocolVersion + "(" + strVersion + ")");
            $("#sys_connections").html(strConnections);
            $("#sys_mnip").html(strMasternodeIP);

            var tmpLabels = [];
            var tmpNumbers = [];
            // payoutsWeek.forEach(function (item) {
            //     tmpLabels.push(item[0]);
            //     tmpNumbers.push(parseInt(item[1]));
            // });

            // chart.destroy();
            // $("#barOptions").html("");
            // barData = {
            //     labels: tmpLabels,
            //     datasets: [
            //         {
            //             label: "Payouts",
            //             backgroundColor: 'transparent',
            //             borderColor: "#18ee30",
            //             borderWidth: 1,
            //             data: tmpNumbers
            //         }
            //     ]
            // };

            // chart = new Chart(c3, {type: 'bar', data: barData, options: payoutOptions});

            var tmpString = "";
            var date;
            var strDate;
            var monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            lastPayouts.forEach(function (item) {
                if (item.time != 0) {
                    date = new Date(item.time * 1000);
                    n =  date.getDate();
                    strDay = n + (n > 0 ? ['th', 'st', 'nd', 'rd'][(n > 3 && n < 21) || n % 10 > 3 ? 0 : n % 10] : '');
                    strDate = strDay + " " + monthNames[date.getMonth()] + " " + date.getFullYear() + " ";
                    (date.getHours() < 10) ? strDate += "0" : strDate = strDate;
                    strDate += date.getHours() + ":" + date.getMinutes() + ":" + date.getSeconds() + " " + (date.getHours() >= 12 ? "PM" : "AM");
                } else
                    strDate = "unknown";
                tmpString += "<tr><td>" + strDate + "</td><td>" + item.block + "</td></tr>";
            });
            if (lastPayouts.length == 0)
                tmpString += "<tr><td> none </td><td> none </td></tr>";
            $("#transactionTable").html(tmpString);

        });
        $.get("ajax.php", {sysinfo: ""}, function (data) {
            var tmpJSON = JSON.parse(data);
            strSysInfoName = tmpJSON.name;
            strSysInfoVersion = tmpJSON.version;
            strSysInfoID = tmpJSON.id;
            strSysInfoIdLike = tmpJSON.id_like;
            strSysInfoPrettyName = tmpJSON.pretty_name;
            strSysInfoVersionID = tmpJSON.version_id;
            strSysInfoHomeUrl = tmpJSON.home_url;
            strSysInfoSupportUrl = tmpJSON.support_url;
            strSysInfoReportUrl = tmpJSON.bug_report_url;
            strSysInfoVersionCodename = tmpJSON.version_codename;
            strSysInfoCodename = tmpJSON.ubuntu_codename;
            strSysInfoTotalRAM = tmpJSON.TotalRAM;


            $("#sys_acitivity").html();
            $("#sys_name").html(strSysInfoName);
            $("#sys_version").html(strSysInfoVersion);
            $("#sys_versionid").html(strSysInfoVersionID);
            $("#sys_codename").html(strSysInfoCodename);

            $("#ramUsageMB").html(strSysInfoTotalRAM + " MB");
            setTimeout(UpdateINFO, 60 * 1000);
        });
    }

    UpdateINFO();
    UpdateRAMandCPU();
    UpdateKey();
    UpdateMasternodeActivity();
    UpdateStakingActivity();

    function logoutattempt(event) {
        event.preventDefault();
        $.post("index.php", {
            fct: "logout"
        },
                function (data) {
                    if (data.search("authorized") !== -1) {
                        location.reload();
                    } else {

                        setTimeout(function () {
                            toastr.options = {
                                "positionClass": "toast-top-right",
                                "closeButton": true,
                                "progressBar": false,
                                "showEasing": "swing",
                                "timeOut": "6000"
                            };
                            toastr.warning('Response: ' + data);
                        }, 1600)
                    }

                });
    }

    $("#btnLogout").click(function (event) {
        logoutattempt(event);
    });
    $("#btnReset").click(function (event) {
        event.preventDefault();
        resetServer();
    });

    $("#submit_masternodekey").click(function (event) {
        event.preventDefault();
        setPrivKey($("#mnKeyInput").val());
    });
    $("#restart_daemon_btn").click(function (event) {
        event.preventDefault();
        restartDaemon();
    });
    $("#reindex_daemon_btn").click(function (event) {
        event.preventDefault();
        reindexDaemon();
    });


});
