Vagrant.configure("2") do |config|
  config.vm.box = "debian-squeeze607-x64-vbox43"
  config.vm.box_url = "http://box.puphpet.com/debian-squeeze607-x64-vbox43.box"

  config.vm.network "private_network", ip: "192.168.50.2"


  config.vm.synced_folder "./", "/home/vagrant/Iterator", id: "vagrant-root", :nfs => false

  #for automate resolving DNS onto VM same as onto host (Debian only host)
  config.vm.synced_folder "/run/resolvconf/", "/tmp/mounted/etc", id: "vagrant-host-etc", :nfs => false

  config.vm.usable_port_range = (2200..2250)
  config.vm.provider :virtualbox do |virtualbox|
    virtualbox.customize ["modifyvm", :id, "--name", "Iterator"]
    virtualbox.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
    virtualbox.customize ["modifyvm", :id, "--natdnsproxy1", "on"]
    virtualbox.customize ["modifyvm", :id, "--memory", "511"]
    virtualbox.customize ["setextradata", :id, "--VBoxInternal2/SharedFoldersEnableSymlinksCreate/v-root", "1"]
  end

  config.vm.provision :shell, :path => "shell/initial-setup.sh"
  config.vm.provision :shell, :path => "shell/update-puppet.sh"
  config.vm.provision :shell, :path => "shell/librarian-puppet-vagrant.sh"
  config.vm.provision :puppet do |puppet|
    puppet.facter = {
      "ssh_username" => "vagrant"
    }

    puppet.manifests_path = "puppet/manifests"
    puppet.options = ["--verbose", "--hiera_config /vagrant/hiera.yaml", "--parser future"]
  end




  config.ssh.username = "vagrant"

  config.ssh.shell = "bash -l"

  config.ssh.keep_alive = true
  config.ssh.forward_agent = false
  config.ssh.forward_x11 = false
  config.vagrant.host = :detect
end

