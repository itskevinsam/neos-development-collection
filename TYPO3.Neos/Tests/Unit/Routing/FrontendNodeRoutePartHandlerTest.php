<?php
namespace TYPO3\Neos\Tests\Unit\Routing;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\Routing\FrontendNodeRoutePartHandler;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;

/**
 * Testcase for the Content Routepart Handler
 *
 */
class FrontendNodeRoutePartHandlerTest extends UnitTestCase {

	/**
	 * @var FrontendNodeRoutePartHandler
	 */
	protected $frontendNodeRoutePartHandler;

	/**
	 * @var ContextFactoryInterface
	 */
	protected $mockContextFactory;

	/**
	 * @var DomainRepository
	 */
	protected $mockDomainRepository;

	/**
	 * @var SiteRepository
	 */
	protected $mockSiteRepository;

	/**
	 * @var ContentContext
	 */
	protected $mockContext;

	/**
	 * @var NodeInterface
	 */
	protected $mockNode;

	/**
	 * @var NodeInterface
	 */
	protected $mockSiteNode;

	public function setUp() {
		$this->frontendNodeRoutePartHandler = $this->getAccessibleMock('TYPO3\Neos\Routing\FrontendNodeRoutePartHandler', array('dummy'));

		$this->mockContextFactory = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface')->getMock();
		$this->inject($this->frontendNodeRoutePartHandler, 'contextFactory', $this->mockContextFactory);

		$this->mockDomainRepository = $this->getMockBuilder('TYPO3\Neos\Domain\Repository\DomainRepository')->disableOriginalConstructor()->getMock();
		$this->inject($this->frontendNodeRoutePartHandler, 'domainRepository', $this->mockDomainRepository);

		$this->mockSiteRepository = $this->getMockBuilder('TYPO3\Neos\Domain\Repository\SiteRepository')->disableOriginalConstructor()->getMock();
		$this->inject($this->frontendNodeRoutePartHandler, 'siteRepository', $this->mockSiteRepository);

		$this->mockContext = $this->getMockBuilder('TYPO3\Neos\Domain\Service\ContentContext')->disableOriginalConstructor()->getMock();

		$this->mockNode = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->getMock();
		$this->mockSiteNode = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\NodeInterface')->getMock();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Neos\Routing\Exception\NoHomepageException
	 */
	public function matchValueThrowsAnExceptionIfNoHomepageExists() {
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));
		$this->frontendNodeRoutePartHandler->_call('matchValue', '');
	}

	/**
	 * @test
	 */
	public function matchValueCreatesContextForLiveWorkspaceByDefault() {
		$self = $this;
		$mockContext = $this->mockContext;
		$this->mockContextFactory->expects($this->once())->method('create')->will($this->returnCallback(function($contextProperties) use ($self, $mockContext) {
			$self->assertSame('live', $contextProperties['workspaceName']);
			return $mockContext;
		}));
		$this->frontendNodeRoutePartHandler->_call('matchValue', 'some/path');
	}

	/**
	 * @test
	 */
	public function matchValueCreatesContextForCustomWorkspaceIfSpecifiedInNodeContextPath() {
		$self = $this;
		$mockContext = $this->mockContext;
		$this->mockContextFactory->expects($this->once())->method('create')->will($this->returnCallback(function($contextProperties) use ($self, $mockContext) {
			$self->assertSame('some-workspace', $contextProperties['workspaceName']);
			return $mockContext;
		}));
		$this->frontendNodeRoutePartHandler->_call('matchValue', 'some/path@some-workspace');
	}

	/**
	 * @test
	 */
	public function matchValueCreatesContextForCurrentDomainIfOneIsFound() {
		$mockDomain = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Domain')->disableOriginalConstructor()->getMock();

		$mockSite = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Site')->disableOriginalConstructor()->getMock();
		$mockDomain->expects($this->atLeastOnce())->method('getSite')->will($this->returnValue($mockSite));

		$this->mockDomainRepository->expects($this->atLeastOnce())->method('findOneByActiveRequest')->will($this->returnValue($mockDomain));

		$self = $this;
		$mockContext = $this->mockContext;
		$this->mockContextFactory->expects($this->once())->method('create')->will($this->returnCallback(function($contextProperties) use ($self, $mockSite, $mockDomain, $mockContext) {
			$self->assertSame($mockDomain, $contextProperties['currentDomain']);
			$self->assertSame($mockSite, $contextProperties['currentSite']);
			return $mockContext;
		}));
		$this->frontendNodeRoutePartHandler->_call('matchValue', 'some/path');
	}

	/**
	 * @test
	 */
	public function matchValueReturnsFalseIfNoWorkspaceCanBeResolved() {
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));
		$this->mockContext->expects($this->atLeastOnce())->method('getWorkspace')->with(FALSE)->will($this->returnValue(NULL));

		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('matchValue', 'some/path'));
	}

	/**
	 * @test
	 */
	public function matchValueReturnsFalseIfNoSiteCanBeResolved() {
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));

		$mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->any())->method('getWorkspace')->with(FALSE)->will($this->returnValue($mockWorkspace));

		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSite')->will($this->returnValue(NULL));

		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('matchValue', 'some/path'));
	}

	/**
	 * @test
	 */
	public function matchValueReturnsFalseIfNoSiteNodeCanBeResolved() {
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));

		$mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->any())->method('getWorkspace')->with(FALSE)->will($this->returnValue($mockWorkspace));

		$mockSite = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Site')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSite')->will($this->returnValue($mockSite));
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSiteNode')->will($this->returnValue(NULL));

		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('matchValue', 'some/path'));
	}

	/**
	 * @test
	 */
	public function matchValueReturnsFalseIfNodeCantBeFetchedFromSiteNode() {
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));

		$mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->any())->method('getWorkspace')->with(FALSE)->will($this->returnValue($mockWorkspace));

		$mockSite = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Site')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSite')->will($this->returnValue($mockSite));

		$this->mockSiteNode->expects($this->atLeastOnce())->method('getNode')->with('some/path')->will($this->returnValue(NULL));
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSiteNode')->will($this->returnValue($this->mockSiteNode));

		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('matchValue', 'some/path'));
	}

	/**
	 * @test
	 */
	public function matchValueSetsValueToContextPathAndReturnsTrueIfNodePathCouldBeResolved() {
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));

		$mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->any())->method('getWorkspace')->with(FALSE)->will($this->returnValue($mockWorkspace));

		$mockSite = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Site')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSite')->will($this->returnValue($mockSite));

		$this->mockSiteNode->expects($this->atLeastOnce())->method('getNode')->with('some/path')->will($this->returnValue($this->mockNode));
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSiteNode')->will($this->returnValue($this->mockSiteNode));

		$this->mockNode->expects($this->atLeastOnce())->method('getContextPath')->will($this->returnValue('some/context/path'));
		$this->assertTrue($this->frontendNodeRoutePartHandler->_call('matchValue', 'some/path'));
		$this->assertSame('some/context/path', $this->frontendNodeRoutePartHandler->getValue());
	}

	/**
	 * Data provider for ... see below
	 */
	public function requestPaths() {
		return array(
			array('homepage', 'homepage'),
			array('homepage.html', 'homepage'),
			array('homepage/subpage.html', 'homepage/subpage'),
			array('homepage/subpage.rss.xml', 'homepage/subpage')
		);
	}

	/**
	 * @test
	 * @dataProvider requestPaths
	 */
	public function findValueToMatchReturnsTheGivenRequestPathUntilTheFirstDot($requestPath, $valueToMatch) {
		$this->assertSame($valueToMatch, $this->frontendNodeRoutePartHandler->_call('findValueToMatch', $requestPath));
	}

	/**
	 * @test
	 */
	public function findValueToMatchRespectsSplitString() {
		$this->frontendNodeRoutePartHandler->setSplitString('baz');

		$expectedResult = 'foo/bar/';
		$actualResult = $this->frontendNodeRoutePartHandler->_call('findValueToMatch', 'foo/bar/baz');
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function resolveValueReturnsFalseIfGivenValueIsNull() {
		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('resolveValue', NULL));
	}

	/**
	 * @test
	 */
	public function resolveValueReturnsFalseIfGivenValueIsNumeric() {
		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('resolveValue', 123));
	}

	/**
	 * @test
	 */
	public function resolveValueCreatesContextForLiveWorkspaceIfGivenValueIsAStringWithoutWorkspaceToken() {
		$self = $this;
		$mockContext = $this->mockContext;
		$this->mockContextFactory->expects($this->once())->method('create')->will($this->returnCallback(function($contextProperties) use ($self, $mockContext) {
			$self->assertSame('live', $contextProperties['workspaceName']);
			return $mockContext;
		}));
		$this->frontendNodeRoutePartHandler->_call('resolveValue', 'some/path');
	}

	/**
	 * @test
	 */
	public function resolveValueCreatesContextForLiveWorkspaceIfGivenValueIsAStringWithWorkspaceToken() {
		$self = $this;
		$mockContext = $this->mockContext;
		$this->mockContextFactory->expects($this->once())->method('create')->will($this->returnCallback(function($contextProperties) use ($self, $mockContext) {
			$self->assertSame('some-workspace', $contextProperties['workspaceName']);
			return $mockContext;
		}));
		$this->frontendNodeRoutePartHandler->_call('resolveValue', 'some/path@some-workspace');
	}

	/**
	 * @test
	 */
	public function resolveValueCreatesContextForCurrentDomainIfGivenValueIsAStringAndADomainIsFound() {
		$mockDomain = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Domain')->disableOriginalConstructor()->getMock();

		$mockSite = $this->getMockBuilder('TYPO3\Neos\Domain\Model\Site')->disableOriginalConstructor()->getMock();
		$mockDomain->expects($this->atLeastOnce())->method('getSite')->will($this->returnValue($mockSite));

		$this->mockDomainRepository->expects($this->atLeastOnce())->method('findOneByActiveRequest')->will($this->returnValue($mockDomain));

		$self = $this;
		$mockContext = $this->mockContext;
		$this->mockContextFactory->expects($this->once())->method('create')->will($this->returnCallback(function($contextProperties) use ($self, $mockSite, $mockDomain, $mockContext) {
			$self->assertSame($mockDomain, $contextProperties['currentDomain']);
			$self->assertSame($mockSite, $contextProperties['currentSite']);
			return $mockContext;
		}));
		$this->frontendNodeRoutePartHandler->_call('resolveValue', 'some/path');
	}

	/**
	 * @test
	 */
	public function resolveValueReturnsFalseIfNoWorkspaceCanBeResolved() {
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));
		$this->mockContext->expects($this->atLeastOnce())->method('getWorkspace')->with(FALSE)->will($this->returnValue(NULL));

		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('resolveValue', 'some/path'));
	}

	/**
	 * @test
	 */
	public function resolveValueReturnsFalseIfNodeCantBeRetrievedFromContext() {
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));

		$mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->atLeastOnce())->method('getWorkspace')->with(FALSE)->will($this->returnValue($mockWorkspace));

		$this->mockContext->expects($this->atLeastOnce())->method('getNode')->with('some/path')->will($this->returnValue(NULL));

		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('resolveValue', 'some/path@context'));
	}

	/**
	 * @test
	 */
	public function resolveValueReturnsFalseIfNodeContextPathIsEmpty() {
		$this->mockNode->expects($this->atLeastOnce())->method('getContext')->will($this->returnValue($this->mockContext));
		$this->mockNode->expects($this->atLeastOnce())->method('getContextPath')->will($this->returnValue(''));

		$this->mockSiteNode->expects($this->atLeastOnce())->method('getPath')->will($this->returnValue(''));
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSiteNode')->will($this->returnValue($this->mockSiteNode));

		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('resolveValue', $this->mockNode));
	}

	/**
	 * @test
	 */
	public function resolveValueReturnsFalseIfNodeContextPathIsNotWithinTheCurrentSiteNode() {
		$this->mockNode->expects($this->atLeastOnce())->method('getContext')->will($this->returnValue($this->mockContext));
		$this->mockNode->expects($this->atLeastOnce())->method('getContextPath')->will($this->returnValue('node/context/path'));

		$this->mockSiteNode->expects($this->atLeastOnce())->method('getPath')->will($this->returnValue('site/root/path'));
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSiteNode')->will($this->returnValue($this->mockSiteNode));

		$this->assertFalse($this->frontendNodeRoutePartHandler->_call('resolveValue', $this->mockNode));
	}

	/**
	 * @test
	 */
	public function resolveValueSetsValueToContextPathAndReturnsTrueIfSpecifiedValueIsAValidNode() {
		$this->mockNode->expects($this->atLeastOnce())->method('getContext')->will($this->returnValue($this->mockContext));
		$this->mockNode->expects($this->atLeastOnce())->method('getContextPath')->will($this->returnValue('the/site/root/the/context/path@some-workspace'));

		$this->mockSiteNode->expects($this->atLeastOnce())->method('getPath')->will($this->returnValue('the/site/root'));
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSiteNode')->will($this->returnValue($this->mockSiteNode));

		$this->assertTrue($this->frontendNodeRoutePartHandler->_call('resolveValue', $this->mockNode));
		$this->assertSame('the/context/path@some-workspace', $this->frontendNodeRoutePartHandler->getValue());
	}

	/**
	 * @test
	 */
	public function resolveValueSetsValueToContextPathAndReturnsTrueIfSpecifiedValueIsAValidNodeContextPath() {
		$this->mockContextFactory->expects($this->any())->method('create')->will($this->returnValue($this->mockContext));

		$this->mockNode->expects($this->atLeastOnce())->method('getContextPath')->will($this->returnValue('the/site/root/the/context/path@some-workspace'));

		$mockWorkspace = $this->getMockBuilder('TYPO3\TYPO3CR\Domain\Model\Workspace')->disableOriginalConstructor()->getMock();
		$this->mockContext->expects($this->atLeastOnce())->method('getWorkspace')->with(FALSE)->will($this->returnValue($mockWorkspace));

		$this->mockContext->expects($this->atLeastOnce())->method('getNode')->with('the/context/path')->will($this->returnValue($this->mockNode));
		$this->mockSiteNode->expects($this->atLeastOnce())->method('getPath')->will($this->returnValue('the/site/root'));
		$this->mockContext->expects($this->atLeastOnce())->method('getCurrentSiteNode')->will($this->returnValue($this->mockSiteNode));

		$this->assertTrue($this->frontendNodeRoutePartHandler->_call('resolveValue', 'the/context/path@some-workspace'));
		$this->assertSame('the/context/path@some-workspace', $this->frontendNodeRoutePartHandler->getValue());
	}

}