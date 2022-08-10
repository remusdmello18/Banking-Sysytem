<?php

namespace jis\a3\model;


class UserAccountModel extends Model
{
    /**
     * @var int $id User ID, auto generated by database
     */
    private $id;

    /**
     * @var string The name to call the user
     */
    private $nickName;

    /**
     * @var string The name used to login
     */
    private $userName;

    /**
     * @var string Hash of user password
     */
    private $password;

    /**
     * @var string the email address of the user
     */
    private $email;

    /**
     * Loads the user account with the given id
     * @param $id int The account id
     * @return $this UserAccountModel The loaded account if successful, null otherwise
     */
    public function loadByID(int $id): ?UserAccountModel
    {
        //query the database
        if (!$result = $this->db->query(
            "SELECT `nickName`, `userName`, `password`, `email` FROM `user_accounts` WHERE `id`=$id;"
        )) {
            die($this->db->error);
        }

        $data = $result->fetch_assoc();
        if ($data === null) {
            return null;
        }

        $this->nickName = $data['nickName'];
        $this->userName = $data['userName'];
        $this->password = $data['password'];
        $this->email = $data['email'];
        $this->id = $id;

        return $this;
    }

    /**
     * Loads the user account with the given user name and password.
     * The password should be hashed already.
     * @param $userName string The account name
     * @param $password string The account password
     * @return UserAccountModel An account if the account exists, null otherwise
     */
    public function loadByUserNameAndPassword(string $userName, string $password): ?UserAccountModel
    {
        if (!$selectAccountByNameAndPassword = $this->db->prepare(
            "SELECT `id`, `nickName`, `email` FROM `user_accounts` WHERE `userName`=? AND `password`=?;"
        )) {
            die($this->db->error);
        }
        $selectAccountByNameAndPassword->bind_param("ss", $userName, $password);
        if (!$result = $selectAccountByNameAndPassword->execute()) {
            $selectAccountByNameAndPassword->close();
            die($this->db->error);
        }
        $selectAccountByNameAndPassword->bind_result($id, $nickName, $email);
        $result = $selectAccountByNameAndPassword->fetch();
        $selectAccountByNameAndPassword->close();
        if ($result) {
            $this->nickName = $nickName;
            $this->userName = $userName;
            $this->password = $password;
            $this->email = $email;
            $this->id = $id;
            return $this;
        }
        return null;
    }

    /**
     * Loads the user account with the given name.
     * @param $userName string The account name
     * @return UserAccountModel An account if the account exists, null otherwise
     */
    public function loadByUserName(string $userName): ?UserAccountModel
    {
        if (!$selectAccountByName = $this->db->prepare(
            "SELECT `id`, `nickName`, `password`, `email` FROM `user_accounts` WHERE `userName`=?;"
        )) {
            die($this->db->error);
        }
        $selectAccountByName->bind_param("s", $userName);
        if (!$result = $selectAccountByName->execute()) {
            $selectAccountByName->close();
            // throw new ...
            die($this->db->error);
        }

        $selectAccountByName->bind_result($id, $nickName, $password, $email);
        $result = $selectAccountByName->fetch();
        $selectAccountByName->close();
        if ($result) {
            $this->nickName = $nickName;
            $this->userName = $userName;
            $this->password = $password;
            $this->email = $email;
            $this->id = $id;

            return $this;
        }
        return null;
    }

    /**
     * Saves user account information to the database. Creates an id if the account doesn't have one already.
     * name and password must not be null.
     * @return $this UserAccountModel
     */
    public function save(): UserAccountModel
    {
        $userName = $this->userName;
        $password = $this->password;
        $email = $this->email;
        if (!isset($this->id)) {
            if (!$stm = $this->db->prepare(
                "INSERT INTO `user_accounts`(`nickName`, `userName`, `password`, `email`) VALUES(?, ?, ?, ?)"
            )) {
                die($this->db->error);
            }
            $stm->bind_param("ssss", $this->nickName, $userName, $password, $email);
            $result = $stm->execute();
            $stm->close();
            if (!$result) {
                die($this->db->error);
            }
            $this->id = $this->db->insert_id;
        } else {
            // saving existing account - perform UPDATE
            if (!$stm = $this->db->prepare(
                "UPDATE `user_accounts` SET `nickName`=?, `userName`=?, `password`=?, `email`=? WHERE `id`=?;"
            )) {
                die($this->db->error);
            }
            $stm->bind_param("ssssi", $this->nickName, $userName, $password, $email, $this->id);
            $result = $stm->execute();
            $stm->close();
            if (!$result) {
                die($this->db->error);
            }
        }

        return $this;
    }

    /**
     * Gets the account password
     * @return string A hash of the account password
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Sets the account password
     * @param string $password A hash of the account password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return string The name to call the user by
     */
    public function getNickName(): string
    {
        return $this->nickName;
    }

    /**
     * @param string $nickName The new name to call the user by
     */
    public function setNickName(string $nickName): void
    {
        $this->nickName = $nickName;
    }

    /**
     * Gets the account name
     * @return string The account name
     */
    public function getUserName(): ?string
    {
        return $this->userName;
    }

    /**
     * Sets the account name
     * @param string $userName The new account name
     */
    public function setUserName(string $userName): void
    {
        $this->userName = $userName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * Returns a unique id for the account. The account must have been saved to have an id.
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public static function create(string $nickName, string $userName, string $password, string $email): UserAccountModel
    {
        $user = new UserAccountModel();
        $user->nickName = $nickName;
        $user->userName = $userName;
        $user->password = $password;
        $user->email = $email;
        return $user;
    }

    /**
     * Gets all transactions made with this user
     */
    public function getTransactions(): \Generator
    {
        if (!$result = $this->db->query("SELECT `id` FROM `transactions` WHERE `userID`=$this->id;")) {
            die($this->db->error);
        }
        $transactionIds = array_column($result->fetch_all(), 0);
        foreach ($transactionIds as $id) {
            // Use a generator to save on memory/resources
            // load accounts from DB one at a time only when required
            yield (new TransactionModel())->loadByID($id);
        }
    }
}