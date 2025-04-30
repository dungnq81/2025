Vagrant.configure("2") do |config|
    # Use Ubuntu 22.04 LTS
    config.vm.box = "ubuntu/jammy64"
    config.vm.box_version = "20241002.0.0"

    # Set hostname for the virtual machine
    config.vm.hostname = "ubuntu-wamp"

    # - Apache (Guest 80 -> Host 8080)
    # - MySQL (Guest 3306 -> Host 3307)
    # - Phpmyadmin (Guest 8081 -> Host 8081)
    config.vm.network "forwarded_port", guest: 80, host: 8080, auto_correct: true
    config.vm.network "forwarded_port", guest: 3306, host: 3307, auto_correct: true
    config.vm.network "forwarded_port", guest: 8081, host: 8081, auto_correct: true

    # Sync folders between host and guest
    config.vm.synced_folder ".", "/vagrant", disabled: true
    config.vm.synced_folder ".", "/var/www/html", type: "virtualbox", create: true, owner: "www-data", group: "www-data", mount_options: ["dmode=755", "fmode=755"]
    config.vm.synced_folder "./.dev/vagrant", "/home/vagrant/config", type: "virtualbox"

    # Configure VM resources
    config.vm.provider "virtualbox" do |vb|
        vb.name = "Ubuntu-#{File.basename(Dir.pwd)}"
        vb.memory = "4096"
        vb.cpus = 4
    end

  # Run shell provisioning script
  config.vm.provision "shell", path: ".dev/provision.sh"
end
