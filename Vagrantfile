Vagrant.configure("2") do |config|

  config.vm.box = "ubuntu-12.04.2"
  config.vm.box_url = "http://puppet-vagrant-boxes.puppetlabs.com/ubuntu-server-12042-x64-vbox4210.box"

  config.vm.network :private_network, ip: "192.168.123.45"
  config.vm.hostname = "epcc.local"

  config.vm.synced_folder "src/", "/mnt/epcc/"

#  Simulate low-resource environment
#
#  config.vm.provider "virtualbox" do |vb|
#    vb.memory = 384
#    vb.cpus = 1
#    vb.customize ["modifyvm", :id, "--cpuexecutioncap", "25"]
#  end

  config.vm.provision :puppet do |puppet|
    puppet.hiera_config_path = "devbox/config/hiera.yaml"
    puppet.module_path = "devbox/puppet/modules"
    puppet.manifests_path = "devbox/puppet/manifests"
    puppet.manifest_file = "site.pp"
  end

end
