<?php

namespace Auth\Service;


use Auth\Entity\MemberInterface;
use Auth\Model\MemberModel;
use Contact\Entity\AddressInterface;
use Contact\Entity\EmailAddressInterface;
use Contact\Entity\ImageInterface;
use Contact\Entity\ContactInterface;
use Contact\Model\AddressModelInterface;
use Contact\Model\ContactModelInterface;
use Contact\Model\EmailAddressModelInterface;
use Contact\Model\ImageModelInterface;

class MemberService
{
    /**
     * @var MemberModel
     */
    protected $memberModel;

    /**
     * @var MemberInterface
     */
    protected $memberPrototype;

    /**
     * @var ContactModelInterface
     */
    protected $contactCommand;

    /**
     * @var ContactInterface
     */
    protected $contactPrototype;

    /**
     * @var EmailAddressModelInterface
     */
    protected $contactEmailCommand;

    /**
     * @var EmailAddressInterface
     */
    protected $contactEmailPrototype;

    /**
     * @var AddressModelInterface
     */
    protected $contactAddressCommand;

    /**
     * @var AddressInterface
     */
    protected $contactAddressPrototype;

    /**
     * @var ImageModelInterface
     */
    protected $contactImageModel;

    /**
     * @var ImageInterface
     */
    protected $contactImagePrototype;

    /**
     * MemberService constructor.
     *
     * @param MemberModel $memberModel
     * @param MemberInterface $memberPrototype
     * @param ContactModelInterface $contactCommand
     * @param ContactInterface $contactPrototype
     * @param EmailAddressModelInterface $contactEmailCommand
     * @param EmailAddressInterface $contactEmailPrototype
     * @param AddressModelInterface $contactAddressCommand
     * @param AddressInterface $contactAddress
     * @param ImageModelInterface $contactImageModel
     * @param ImageInterface $contactImage
     */
    public function __construct(
        MemberModel $memberModel,
        MemberInterface $memberPrototype,
        ContactModelInterface $contactCommand,
        ContactInterface $contactPrototype,
        EmailAddressModelInterface $contactEmailCommand,
        EmailAddressInterface $contactEmailPrototype,
        AddressModelInterface $contactAddressCommand,
        AddressInterface $contactAddress,
        ImageModelInterface $contactImageModel,
        ImageInterface $contactImage
    )
    {
        $this->memberModel = $memberModel;
        $this->memberPrototype = $memberPrototype;
        $this->contactCommand = $contactCommand;
        $this->contactPrototype = $contactPrototype;
        $this->contactEmailCommand = $contactEmailCommand;
        $this->contactEmailPrototype = $contactEmailPrototype;
        $this->contactAddressCommand = $contactAddressCommand;
        $this->contactAddressPrototype = $contactAddress;
        $this->contactImageModel = $contactImageModel;
        $this->contactImagePrototype = $contactImage;
    }


    /**
     * Register a new member based on the information retrieved from LinkedIn
     *
     * @param array $memberProfileData
     * @param string $accessToken
     * @return MemberInterface
     */
    public function registerNewMember(array $memberProfileData, $accessToken)
    {
        $memberClass = get_class($this->memberPrototype);
        $newMember = new $memberClass(0, $memberProfileData['id'], $accessToken);
        $memberEntity = $this->memberModel->saveMember($newMember);

        $contactClass = get_class($this->contactPrototype);
        $newContact = new $contactClass(
            0,
            $memberEntity->getMemberId(),
            $memberProfileData['firstName'],
            $memberProfileData['lastName']
        );
        $contactEntity = $this->contactCommand->saveContact($memberEntity->getMemberId(), $newContact);

        $contactEmailClass = get_class($this->contactEmailPrototype);
        $newContactEmail = new $contactEmailClass(
            0,
            $memberEntity->getMemberId(),
            $contactEntity->getContactId(),
            $memberProfileData['emailAddress'],
            true
        );
        $contactEmailEntity = $this->contactEmailCommand->saveEmailAddress($contactEntity->getContactId(), $newContactEmail);

        $contactAddressClass = get_class($this->contactAddressPrototype);
        $newContactAddress = new $contactAddressClass(
            0,
            $memberEntity->getMemberId(),
            $contactEntity->getContactId(),
            '', // street1
            '', // street2
            '', // postcode
            '', // city
            '', // province
            (isset ($memberProfileData['location']['country']['code']) ?
                strtoupper($memberProfileData['location']['country']['code']) :
                '')
        );
        $contactAddressEntity = $this->contactAddressCommand->saveAddress($contactEntity->getContactId(), $newContactAddress);

        $contactImageClass = get_class($this->contactImagePrototype);
        $newContactImage = new $contactImageClass(
            0,
            $memberEntity->getMemberId(),
            $contactEntity->getContactId(),
            $memberProfileData['pictureUrl'],
            true
        );
        $contactImageEntity = $this->contactImageModel->saveImage($newContactImage);

        return $memberEntity;
    }

    /**
     * Update an existing member
     *
     * @param array $memberProfileData
     * @param string $accessToken
     * @return MemberInterface
     */
    public function updateMember(array $memberProfileData, $accessToken)
    {
        $member = $this->memberModel->getMemberByLinkedinId($memberProfileData['id']);
        $memberClass = get_class($this->memberPrototype);
        $memberEntity = new $memberClass(
            $member->getMemberId(),
            $member->getLinkedinId(),
            $accessToken
        );
        $this->memberModel->saveMember($memberEntity);
        return $memberEntity;
    }

}